<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor;

use Drupal\node\Entity\Node;
use Drupal\nys_openleg\Api\Request;
use Drupal\nys_openleg\BillHelper;
use Drupal\nys_openleg_imports\ImportProcessorBase;

/**
 * Openleg Import Processor plugin for agendas.
 *
 * @OpenlegImportProcessor(
 *   id = "agendas",
 *   label = @Translation("Agendas"),
 *   description = @Translation("Import processor plugin for agendas."),
 *   bundle = "agenda"
 * )
 */
class Agendas extends ImportProcessorBase {

  /**
   * {@inheritDoc}
   *
   * Agenda responses contain agendas for every committee for a single week.
   * Further, every committee agenda may have multiple addenda.  This iterates
   * over every addendum for every committee, saving each as a node.  Success
   * is reported only if all addenda were saved successfully.
   */
  public function process(): bool {

    $ret = TRUE;
    foreach (($this->item->result()->committeeAgendas->items ?? []) as $agenda) {
      foreach (($agenda->addenda->items ?? []) as $addendum) {
        $title = $this->formatAddendumTitle($addendum);
        $node = $this->resolveNode(['title' => $title]);
        $ret &= $this->transcribeToNode($addendum, $node);
        if (!$ret) {
          $this->logger->error('Failed to transcribe agenda @title', ['@title' => $title]);
          break;
        }
      }
    }

    if ($ret) {
      try {
        foreach ($this->nodes as $node) {
          $node->save();
        }
      }
      catch (\Throwable $e) {
        $ret = FALSE;
        $this->logger->error(
              'Failed to save imported agenda @title',
              [
                '@title' => $this->getId(),
                '@message' => $e->getMessage(),
              ]
          );
      }
    }

    return $ret;
  }

  /**
   * {@inheritDoc}
   *
   * $item is an addendum item.  These can be found in agenda responses at:
   * result->committeeAgendas->items[<idx>]->addenda->items[<idx>].
   */
  public function transcribeToNode(object $item, Node $node): bool {
    $success = TRUE;

    $values = [];
    $comm_name = $item->committeeId->name ?? '';
    try {
      $committee = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'committees', 'name' => $comm_name]);
    }
    catch (\Throwable $e) {
      $committee = [];
    }
    $committee = reset($committee);
    if (!$committee) {
      $this->logger->warning('Committee @name was not found', ['@name' => $comm_name]);
    }

    $meeting_date = $item->meeting->meetingDateTime . ' ' . date_default_timezone_get();
    $values = [
      'field_ol_agenda_addendum' => $item->addendumId,
      'field_ol_agenda_location' => $item->meeting->location,
      'field_ol_agenda_notes' => $item->meeting->notes,
      'field_ol_committee' => $committee ?: NULL,
      'field_ol_committee_name' => $comm_name,
      'field_ol_meeting_date' => gmdate(Request::OPENLEG_TIME_SIMPLE, strtotime($meeting_date)),
      'field_ol_week' => $item->agendaId->number,
      'field_ol_year' => $item->agendaId->year,
      'field_from_openleg' => TRUE,
    ];

    // Add all the things to the node.
    try {
      foreach ($values as $field => $val) {
        $node->set($field, $val);
      }
      if ($item->hasVotes ?? FALSE) {
        $this->populateBillVotes($node, $item);
      }
    }
    catch (\Throwable $e) {
      $success = FALSE;
      $this->logger->error(
            'Failed to set properties for node @node from @item',
            [
              '@node' => $node->id(),
              '@item' => $node->get('title')->getValue(),
              '@message' => $e->getMessage(),
            ]
        );
    }

    return $success;
  }

  /**
   * {@inheritDoc}
   */
  public function getId(): string {
    $year = $this->item->result()->id->year ?? '';
    $num = $this->item->result()->id->number ?? '';
    return ($year && $num) ? "$year/$num" : '';
  }

  /**
   * Generates the node title for a committee addenda/meeting.
   *
   * @param object $meeting
   *   An OpenLeg object representing an addendum.  Found in an agenda response
   *   at result->committeeAgendas->items[<idx>]->addenda->items[<idx>].
   *
   * @return string
   *   The formatted title.
   */
  public function formatAddendumTitle(object $meeting): string {
    $committee = $meeting->committeeId->name ?? '';
    $year = $meeting->agendaId->year ?? '';
    $week = $meeting->agendaId->number ?? '';
    $time = $meeting->meeting->meetingDateTime ?? '';

    return ($year && $week && $time && $committee)
        ? "$year Week $week $committee ($time)"
        : '';
  }

  /**
   * Populates the bill vote paragraph entities for an agenda.
   *
   * Note that this will also delete all existing paragraph entities assigned
   * to the node.  This is meant to overwrite the field.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node receiving the new paragraphs.
   * @param object $item
   *   An addendum item, found in Openleg Agenda responses at:
   *   result->committeeAgendas->items[<idx>]->addenda->items[<idx>].
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function populateBillVotes(Node $node, object $item) {
    // For reference.
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $vote_types = [
      'EXC' => 'excused',
      'AYEWR' => 'aye_wr',
      'AYE' => 'aye',
      'NAY' => 'nay',
      'ABD' => 'abstained',
      'ABS' => 'absent',
    ];

    // Note the current paragraphs for later and initialize the "new" list.
    $old_votes = array_map(
          function ($v) {
              return $v['target_id'];
          },
          $node->get('field_ol_agenda_bills')->getValue()
      );
    $new_votes = [];

    // Organize the bill and vote objects for easier reference.
    $bills = [];
    foreach (($item->bills->items ?? []) as $bill) {
      $name = BillHelper::formatTitle($bill->billId);
      $bills[$name] = $bill;
    }
    $votes = [];
    foreach (($item->voteInfo->votesList->items ?? []) as $vote) {
      $name = BillHelper::formatTitle($vote->bill);
      $votes[$name] = $vote;
    }

    // Resolve the node id references for each bill.
    if (count($bills)) {
      $bill_storage = $this->entityTypeManager->getStorage('node');
      $bill_refs = $bill_storage->loadByProperties(
            [
              'type' => 'bill',
              'title' => array_keys($bills),
            ]
        );
      foreach ($bill_refs as $nid => $bill_node) {
        /**
         * @var \Drupal\node\Entity\Node $bill_node
*/
        $title = $bill_node->getTitle();
        if (array_key_exists($title, $votes)) {
          $votes[$title]->nid = $nid;
        }
      }
    }

    foreach ($votes as $bill_key => $vote) {
      if ($vote->nid ?? 0) {
        /**
         * @var \Drupal\paragraphs\Entity\Paragraph $new_pg
*/
        $new_pg = $storage->create(['type' => 'agenda_bills']);
        $new_pg->set('field_ol_bill', $vote->nid);
        $new_pg->set('field_ol_bill_message', $bills[$bill_key]->message);
        $new_pg->set('field_ol_bill_name', json_encode($bills[$bill_key]));
        foreach ($vote_types as $prop => $field) {
          $value = $vote->vote->memberVotes->items->{$prop}->size ?? 0;
          if ($value) {
            $new_pg->set('field_ol_' . $field . '_count', $value);
          }
        }
        $new_pg->save();
        $new_votes[] = $new_pg;
      }
    }

    // Delete the now-obsolete paragraphs.
    if (count($old_votes)) {
      $storage->delete($storage->loadMultiple($old_votes));
    }

    // Set the new paragraph references.
    $node->set('field_ol_agenda_bills', $new_votes);
  }

}
