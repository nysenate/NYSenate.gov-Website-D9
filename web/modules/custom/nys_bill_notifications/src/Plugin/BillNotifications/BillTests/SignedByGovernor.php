<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being signed by the Governor.
 *
 * @BillTest(
 *   id = "signed_by_governor",
 *   label = @Translation("Signed By the Governor"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "SIGNED_BY_GOV",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "SIGNED_BY_GOV",
 *     },
 *   },
 *   priority = 17
 * )
 */
class SignedByGovernor extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has been signed by the Governor.";
  }

}
