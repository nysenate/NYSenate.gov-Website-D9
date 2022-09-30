<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being vetoed.
 *
 * @BillTest(
 *   id = "vetoed",
 *   label = @Translation("Vetoed"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "VETOED",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "VETOED",
 *     },
 *   },
 *   priority = 16
 * )
 */
class Vetoed extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has been vetoed.";
  }

}
