<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for a new active version.
 *
 * @BillTest(
 *   id = "new_active_version",
 *   label = @Translation("New Active Version"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "NEW_ACTIVE_VER",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Active Version" = TRUE
 *     },
 *   },
 *   priority = 8
 * )
 */
class NewActiveVersion extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $amend = trim($update->fields->{'Active Version'});
    $amend = ($amend) ? "amendment $amend" : "the original version";
    return "Bill " . $update->id->basePrintNoStr . "'s active version is now $amend.";
  }

}
