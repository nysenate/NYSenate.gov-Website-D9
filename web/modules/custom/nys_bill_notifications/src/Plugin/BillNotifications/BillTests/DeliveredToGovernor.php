<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being delivered to the Governor.
 *
 * @BillTest(
 *   id = "delivered_to_governor",
 *   label = @Translation("Delivered to the Governor"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "DELIVERED_TO_GOV",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "DELIVERED_TO_GOV",
 *     },
 *   },
 *   priority = 15
 * )
 */
class DeliveredToGovernor extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has been delivered to the Governor.";
  }

}
