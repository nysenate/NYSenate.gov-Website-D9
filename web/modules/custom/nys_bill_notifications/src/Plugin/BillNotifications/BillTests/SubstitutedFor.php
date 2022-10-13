<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for substituting for another bill.
 *
 * @BillTest(
 *   id = "subtituted_for",
 *   label = @Translation("Substituted For"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "SUBTITUTED_FOR",
 *   pattern = {
 *     "scope" = "Bill Amendment Action",
 *     "action" = "Insert",
 *     "fields" = {
 *       "Text" = "SUBSTITUTED FOR *",
 *     },
 *   },
 *   priority = 14
 * )
 */
class SubstitutedFor extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $pn = str_replace('SUBSTITUTED FOR ', '', $update->fields->Text);
    return $update->id->basePrintNoStr . " was substituted for $pn.";
  }

}
