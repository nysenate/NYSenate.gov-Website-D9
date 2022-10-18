<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for having a new Same As.
 *
 * @BillTest(
 *   id = "new_same_as",
 *   label = @Translation("New Same As"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "NEW_SAME_AS",
 *   pattern = {
 *     "scope" = "Bill Amendment Same As",
 *     "action" = "Insert",
 *     "fields" = {
 *       "Same As Session Year" = TRUE,
 *       "Same As Bill Print No" = TRUE,
 *     },
 *   },
 *   priority = 10
 * )
 */
class NewSameAs extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $same_as = $update->fields->{'Same As Session Year'} . '-' .
      $update->fields->{'Same As Bill Print No'};
    if (trim($update->fields->{'Same As Amend Version'})) {
      $same_as .= $update->fields->{'Same As Amend Version'};
    }
    return $update->id->basePrintNoStr . " has a new Same As bill: $same_as";
  }

}
