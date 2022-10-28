<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for going to the senate floor.
 *
 * @BillTest(
 *   id = "senate_floor",
 *   label = @Translation("Gone to the Senate Floor"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "SENATE_FLOOR",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "SENATE_FLOOR",
 *     },
 *   },
 *   priority = 3
 * )
 */
class SenateFloor extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has gone to the Senate Floor.";
  }

}
