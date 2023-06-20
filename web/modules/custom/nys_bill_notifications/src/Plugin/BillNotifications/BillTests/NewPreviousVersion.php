<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for a new previous version.
 *
 * @BillTest(
 *   id = "new_previous_version",
 *   label = @Translation("New Previous Version"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "NEW_PREV_VER",
 *   pattern = {
 *     "scope" = "Bill Previous Version",
 *     "action" = "Insert",
 *     "fields" = {
 *       "Prev Bill Session Year" = TRUE,
 *       "Prev Bill Print No" = TRUE,
 *     },
 *   },
 *   priority = 6
 * )
 */
class NewPreviousVersion extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $prev = $update->fields->{'Prev Bill Session Year'} . '-' .
        $update->fields->{'Prev Bill Print No'};
    if (trim($update->fields->{'Prev Amend Version'})) {
      $prev .= $update->fields->{'Prev Amend Version'};
    }
    return "Bill " . $update->id->basePrintNoStr . " was assigned $prev as a previous version.";
  }

}
