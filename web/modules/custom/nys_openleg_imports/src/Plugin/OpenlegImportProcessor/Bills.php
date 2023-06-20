<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor;

use Drupal\node\Entity\Node;
use Drupal\nys_openleg\Api\Request;
use Drupal\nys_openleg\BillHelper;
use Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseItem;
use Drupal\nys_openleg_imports\ImportProcessorBase;

/**
 * Openleg Import Processor plugin for floor transcripts.
 *
 * @OpenlegImportProcessor(
 *   id = "bills",
 *   label = @Translation("Bills and Resolutions"),
 *   description = @Translation("Import processor plugin for bills and
 *   resolutions."), bundle = "bill"
 * )
 */
class Bills extends ImportProcessorBase {

  /**
   * {@inheritdoc}
   *
   * This needs to be a Bill response object.
   *
   * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\Bill
   */
  protected ResponseItem $item;

  /**
   * {@inheritDoc}
   */
  public function init(ResponseItem $item): ImportProcessorBase {
    // Reclaim the node cache, in case this processor is reused.
    foreach ($this->nodes as $k => $v) {
      unset($this->nodes[$k]);
    }
    return parent::init($item);
  }

  /**
   * {@inheritDoc}
   *
   * Bill responses contain information about all amended versions of a
   * single print number (e.g., S500, S500A, S500B, etc).  This iterates
   * over all versions in a bill response.  Success is reported only if
   * all versions were saved successfully.
   */
  public function process(): bool {

    $ret = TRUE;
    $result = $this->item->result();
    $base_title = BillHelper::formatTitle($result, TRUE);
    $subbed_by = $result->substitutedBy->basePrintNo ?? '';
    foreach ($result->amendmentVersions->items as $version) {
      // Get a node for this amendment.  Report and skip on failure.
      $title = $base_title . $version;
      $type = $result->billType->resolution ? 'resolution' : 'bill';
      $node = $this->resolveNode(['title' => $title, 'type' => $type]);
      if (!$node) {
        $this->logger->error('Failed to resolve a node for @title', ['@title' => $title]);
        break;
      }

      // Get the amendment item, or report and skip.
      $item = ($result->amendments->items->{$version}) ?? NULL;
      if (!$item) {
        $this->logger->error('Could not read amendment item @title', ['@title' => $title]);
        break;
      }

      // Add the substitution, if it exists, and transcribe the rest.
      if ($subbed_by) {
        $node->set('field_ol_substituted_by', $subbed_by);
      }
      $ret &= $this->transcribeToNode($item, $node);
      if (!$ret) {
        $this->logger->error('Failed to transcribe amendment @title', ['@title' => $title]);
        break;
      }
    }

    // Only save if all transcriptions succeeded.
    if ($ret) {
      try {
        foreach ($this->nodes as $node) {
          $node->save();
        }
      }
      catch (\Throwable $e) {
        $ret = FALSE;
        $this->logger->error(
              'Failed to save imported amendment @title',
              [
                '@title' => BillHelper::formatTitle($this->item),
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
   * This handles a single amendment of a bill.
   */
  public function transcribeToNode(object $item, Node $node): bool {
    // Set up some references.
    $success = TRUE;
    $result = $this->item->result();
    $is_resolution = $result->billType->resolution;
    $sponsor = $result->sponsor->member ?? new \stdClass();

    // Most of the fields, in alphabetical order.
    $values = [
      'field_ol_active_version' => $result->activeVersion,
      'field_ol_all_statuses' => json_encode($is_resolution ? $result->actions : $result->milestones),
      'field_ol_amendments' => json_encode($result->amendments->items),
      'field_ol_base_print_no' => $item->basePrintNo,
      'field_ol_chamber' => strtolower($result->billType->chamber),
      'field_ol_full_text' => $item->fullText,
      'field_ol_has_same_as' => (int) ($item->sameAs->size > 0),
      'field_ol_is_amended' => (int) $this->item->isAmended(),
      'field_ol_law_section' => $item->lawSection,
      'field_ol_memo' => $item->memo,
      'field_ol_name' => $result->title,
      'field_ol_previous_versions' => json_encode($result->previousVersions->items),
      'field_ol_print_no' => $item->printNo,
      'field_ol_publish_date' => date(Request::OPENLEG_TIME_SIMPLE, strtotime($result->publishedDateTime)),
      'field_ol_same_as' => json_encode($item->sameAs->items),
      'field_ol_session' => $item->session,
      'field_ol_sponsor' => BillHelper::findSenatorFromMember($sponsor),
      'field_ol_sponsor_name' => $sponsor->shortName ?? '',
      'field_ol_summary' => $result->summary,
    ];

    // For bills only.
    if (!$is_resolution) {
      $values['field_ol_version'] = $item->version ?? '';
      $values['field_ol_is_active_version'] = (int) ($item->version == $result->activeVersion);
      $values['field_ol_all_actions'] = json_encode($result->actions);
      $values['field_ol_law_code'] = $item->lawCode;
      $values['field_ol_last_status'] = $result->status->statusType;

      // Add last status from milestones.
      if ($milestone = end($result->milestones->items)) {
        $values['field_ol_latest_status_committee'] = $milestone->committeeName;
        if ($milestone->actionDate) {
          $values['field_ol_last_status_date'] = $milestone->actionDate;
        }
      }

      // Add program info, if it exists.
      if (!empty($result->programInfo)) {
        $values['field_ol_program_info'] = $result->programInfo->name;
        $values['field_ol_program_info_seq'] = $result->programInfo->sequenceNo;
      }

    }

    // Compile and add the extra sponsor information.
    $sponsors = [
      'additionalSponsors' => 'add_sponsor',
      'coSponsors' => 'co_sponsor',
      'multiSponsors' => 'multi_sponsor',
    ];
    foreach ($sponsors as $prop => $field) {
      $nodes = [];
      $items = $item->{$prop}->items ?? [];
      foreach ($items as $member) {
        $nodes[] = BillHelper::findSenatorFromMember($member);
      }
      $field_name = 'field_ol_' . $field . 's';
      $all_name = 'field_ol_' . $field . '_names';
      $values[$field_name] = array_filter(array_unique($nodes));
      $values[$all_name] = json_encode($items);
    }

    // Add all the things to the node.
    try {
      foreach ($values as $field => $val) {
        $node->set($field, $val);
      }
      if (!$is_resolution) {
        $this->populateVotes($node);
      }
    }
    catch (\Throwable $e) {
      $success = FALSE;
      $this->logger->error(
            'Failed to set properties for node @node from @item',
            [
              '@node' => $node->id(),
              '@item' => BillHelper::formatTitle($this->item),
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
    return $this->item->result()->basePrintNoStr ?? '';
  }

  /**
   * Populates the voting session paragraph entities for a bill.
   *
   * Note that this will also delete all existing paragraph entities assigned
   * to the bill.  This is meant to overwrite.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The bill node receiving the voting records.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function populateVotes(Node $node) {
    // For reference.
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $all_votes = $this->item->result()->votes->items ?? [];
    $vote_types = [
      'EXC' => 'excused',
      'AYEWR' => 'aye_wr',
      'AYE' => 'aye',
      'NAY' => 'nay',
      'ABD' => 'abstained',
      'ABS' => 'absent',
    ];

    // Note the current paragraphs for later and initialize the "new" list.
    $old_vote_ids = array_map(
          function ($v) {
              return $v['target_id'];
          },
          $node->get('field_ol_votes')->getValue()
      );
    $new_votes = [];

    // Iterate through the collection of vote sessions, creating new paragraph
    // for each one.  Save the new paragraphs for addition to the node.
    foreach ($all_votes as $vote) {
      /**
* @var \Drupal\paragraphs\Entity\Paragraph $paragraph
*/
      $paragraph = $storage->create(['type' => 'votes']);

      // Set vote date and type.
      $paragraph->set('field_publication_date', $vote->voteDate);
      $paragraph->set('field_ol_vote_type', $vote->voteType);

      // If this was for a senate committee, set the committee reference.
      if ((($vote->committee->chamber ?? '') == "SENATE")) {
        $comm_search = [
          'vid' => 'committees',
          'name' => ($vote->committee->name ?? ''),
        ];
        $comm_tid = array_keys(
              $this->entityTypeManager
                ->getStorage('taxonomy_term')
                ->loadByProperties($comm_search)
          );
        $paragraph->set('field_ol_committee', $comm_tid);
      }

      // Set counts, members, and names.
      foreach (($vote->memberVotes->items ?? []) as $type => $vote_item) {
        if ($base_name = ($vote_types[$type] ?? '')) {
          $paragraph->set('field_ol_' . $base_name . '_count', count($vote_item->items ?? []));
          $paragraph->set('field_ol_' . $base_name . '_members', BillHelper::findSenatorsByMemberInfo($vote_item->items ?? []));
          $paragraph->set('field_ol_' . $base_name . '_names', json_encode($vote_item->items ?? []));
        }
      }

      // Save the new paragraph.
      $paragraph->save();
      $new_votes[] = $paragraph;
    }

    // Delete the now-obsolete paragraphs.
    if (count($old_vote_ids)) {
      $storage->delete($storage->loadMultiple($old_vote_ids));
    }

    // Set the new paragraph references.
    $node->set('field_ol_votes', $new_votes);
  }

}
