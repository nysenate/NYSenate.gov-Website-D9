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
   * Returns the print number of the target bill for a same_as update.
   */
  protected function getSameAsPrint(object $update): string {
    $same_as = $update->fields->{'Same As Bill Print No'};
    if (trim($update->fields->{'Same As Amend Version'})) {
      $same_as .= $update->fields->{'Same As Amend Version'};
    }
    return $same_as;
  }

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return $update->id->basePrintNoStr . " has a new Same As bill: " .
        $this->getSameAsPrint($update);
  }

  /**
   * {@inheritDoc}
   *
   * Adds the print number for the "same as" bill.
   */
  public function doContext(object $update): array {
    return ['bill.same_as' => $this->getSameAsPrint($update)];
  }

}
