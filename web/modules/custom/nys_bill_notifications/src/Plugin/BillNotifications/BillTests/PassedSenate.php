<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for passing senate.
 *
 * @BillTest(
 *   id = "passed_senate",
 *   label = @Translation("Passed Senate"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "PASSED_SENATE",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "PASSED_SENATE",
 *     },
 *   },
 *   priority = 13
 * )
 */
class PassedSenate extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has passed the Senate.";
  }

}
