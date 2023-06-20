<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for a removed previous version.
 *
 * @BillTest(
 *   id = "remove_previous_version",
 *   label = @Translation("Remove Previous Version"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "REMOVE_PREV_VER",
 *   pattern = {
 *     "scope" = "Bill Previous Version",
 *     "action" = "Delete",
 *     "fields" = {
 *       "Prev Bill Session Year" = TRUE,
 *       "Prev Bill Print No" = TRUE,
 *     },
 *   },
 *   priority = 7
 * )
 */
class RemovePreviousVersion extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $prev = $update->fields->{'Prev Bill Session Year'} . '-' .
        $update->fields->{'Prev Bill Print No'};
    if (trim($update->fields->{'Prev Amend Version'})) {
      $prev .= $update->fields->{'Prev Amend Version'};
    }
    return "Bill " . $update->id->basePrintNoStr . " had $prev removed as a previous version.";
  }

}
