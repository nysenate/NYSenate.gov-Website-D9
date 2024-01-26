<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for  being adopted.
 *
 * @BillTest(
 *   id = "adopted",
 *   label = @Translation("Adopted"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "ADOPTED",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "ADOPTED",
 *     },
 *   },
 *   disabled = true,
 *   priority = 0
 * )
 */
class Adopted extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has been adopted.";
  }

}
