<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for a new amendment.
 *
 * @BillTest(
 *   id = "new_amendment",
 *   label = @Translation("New Amendment"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "NEW_AMENDMENT",
 *   pattern = {
 *     "action" = "Insert",
 *     "scope" = "Bill Amendment Publish Status",
 *     "fields" = {
 *       "Bill Amend Version" = TRUE,
 *       "Published" = "t",
 *     },
 *   },
 *   priority = 9
 * )
 */
class NewAmendment extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $amend = trim($update->fields->{'Bill Amend Version'});
    $amend = ($amend) ? "amendment $amend" : "the original version";
    return "Bill " . $update->id->basePrintNoStr . " has a new amendment ($amend).";
  }

}
