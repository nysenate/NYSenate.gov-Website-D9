<?php

namespace Drupal\nys_openleg_imports\Plugin\OpenlegImportProcessor;

use Drupal\node\Entity\Node;
use Drupal\nys_openleg\BillHelper;
use Drupal\nys_openleg_imports\ImportProcessorBase;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Openleg Import Processor plugin for calendars.
 *
 * @OpenlegImportProcessor(
 *   id = "calendars",
 *   label = @Translation("Calendars"),
 *   description = @Translation("Import processor plugin for calendars."),
 *   bundle = "calendar"
 * )
 */
class Calendars extends ImportProcessorBase {

  /**
   * {@inheritDoc}
   */
  public function transcribeToNode(object $item, Node $node): bool {
    $ret = TRUE;

    try {
      $node->set('field_ol_year', $item->year);
      $node->set('field_ol_cal_no', $item->calendarNumber);
      $node->set('field_ol_calendar_date', strtotime($item->calDate));

      $this->populateCalendar($node);
    }
    catch (\Throwable $e) {
      $ret = FALSE;
    }

    return $ret;
  }

  /**
   * Populates a list of bills into a calendar paragraph.
   *
   * The lists of bills in an individual calendar are identified by the bill's
   * print number, and each bill's node id must be correlated to their sequence
   * number.  This compiles a list of all bill titles, loads the nodes, then
   * appends the sequence and node id simultaneously to preserve correlation.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $pg
   *   The calendar paragraph entity to populate.
   * @param array $items
   *   An array of bill items, such as is found in a calendar response, e.g.,
   *   <response_object>->result->activeLists->items[x]->entries->items.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function populateBillData(Paragraph $pg, array $items) {
    // Initialize.
    $bills = [];

    // Organize the bill titles and calendar numbers.
    foreach ($items as $bill) {
      $seq = $bill->billCalNo ?? 0;
      if ($seq) {
        $bills[BillHelper::formatTitle($bill)] = ['seq' => $seq];
      }
    }

    // Try to attach the node id to the title.
    $bill_storage = $this->entityTypeManager->getStorage('node');
    if (count($bills)) {
      $nodes = $bill_storage->loadByProperties(
            [
              'title' => array_keys($bills),
              'type' => 'bill',
            ]
        );
      foreach ($nodes as $nid => $node) {
        /**
         * @var \Drupal\node\Entity\Node $node
*/
        $title = $node->get('title')->value;
        if (array_key_exists($title, $bills)) {
          $bills[$title]['nid'] = $nid;
        }
      }
    }

    foreach ($bills as $data) {
      if (($data['nid'] ?? 0) && isset($data['seq'])) {
        $pg->get('field_ol_bill')->appendItem($data['nid']);
        $pg->get('field_ol_bill_cal_number')->appendItem($data['seq']);
      }
    }
  }

  /**
   * Populates the field_ol_cal field on a calendar node.
   *
   * This iterates through floor calendars, supplemental calendars, and active
   * lists.  Supplemental calendars and active lists can have multiple sections
   * to populate, each of which needs its own paragraph entity.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The receiving calendar node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function populateCalendar(Node $node) {
    $result = $this->item->result();
    $storage = $this->entityTypeManager->getStorage('paragraph');

    // Note the current paragraphs for later and initialize the "new" list.
    $old_ids = array_map(
          function ($v) {
              return $v['target_id'];
          },
          $node->get('field_ol_cal')->getValue()
      );
    $new_ids = [];

    $to_import = [
      'floor_calendar' => [
        'items' => $result->floorCalendar->entriesBySection->items ?? new \stdClass(),
        'version' => $result->floorCalendar->version ?? '',
        'seq' => 0,
        'type' => 'floor_calendar',
      ],
    ];
    foreach (($result->supplementalCalendars->items ?? []) as $version => $items) {
      $to_import['supplemental_' . $version] = [
        'items' => $items->entriesBySection->items ?? new \stdClass(),
        'version' => $version,
        'seq' => 0,
        'type' => 'supplemental_calendar',
      ];
    }
    foreach (($result->activeLists->items ?? []) as $version => $items) {
      $to_import['active_' . $version] = [
        'items' => (object) ['active_' . $version => (object) ['items' => $items->entries->items ?? []]],
        'version' => $version,
        'seq' => $items->sequenceNumber,
        'type' => 'active_list',
      ];
    }

    foreach ($to_import as $one_import) {
      if (count(get_object_vars($one_import['items']))) {
        /**
         * @var \Drupal\paragraphs\Entity\Paragraph $new_pg
*/
        $new_pg = $storage->create(['type' => 'calendar']);
        $all_bills = [];
        foreach ($one_import['items'] as $reading) {
          $this->populateBillData($new_pg, ($reading->items ?? []));
          $all_bills += $reading->items;
        }
        $new_pg->set('field_ol_type', $one_import['type']);
        $new_pg->set('field_ol_bill_names', json_encode($all_bills));
        $new_pg->set('field_ol_version', $one_import['version']);
        $new_pg->set('field_ol_sequence_no', $one_import['seq']);
        $new_pg->save();
        $new_ids[] = $new_pg;
      }
    }

    // Delete the now-obsolete paragraphs.
    if (count($old_ids)) {
      $storage->delete($storage->loadMultiple($old_ids));
    }

    // Set the new paragraph references.
    $node->set('field_ol_cal', $new_ids);
  }

  /**
   * {@inheritDoc}
   */
  public function getId(): string {
    $year = $this->item->result()->year ?? '';
    $num = $this->item->result()->calendarNumber ?? '';
    return ($year && $num) ? "$year - $num" : '';
  }

}
