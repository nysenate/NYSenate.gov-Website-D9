<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being substituted by another bill.
 *
 * @BillTest(
 *   id = "subtituted_by",
 *   label = @Translation("Substituted By"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "SUBTITUTED_BY",
 *   pattern = {
 *     "scope" = "Bill Amendment Action",
 *     "action" = "Insert",
 *     "fields" = {
 *       "Text" = "SUBSTITUTED BY *",
 *     },
 *   },
 *   priority = 14
 * )
 */
class SubstitutedBy extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $pn = str_replace('SUBSTITUTED BY ', '', $update->fields->Text);
    return "Bill " . $update->id->basePrintNoStr . " was substituted by $pn.";
  }

}
