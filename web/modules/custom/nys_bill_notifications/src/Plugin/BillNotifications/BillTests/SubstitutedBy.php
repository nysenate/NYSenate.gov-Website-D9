<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being substituted by another bill.
 *
 * @BillTest(
 *   id = "substituted_by",
 *   label = @Translation("Substituted By"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "SUBSTITUTE_BY",
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
   * Parses update text to get the substituting bill print number.
   */
  protected function getSubbedPrint(object $update): string {
    return str_replace('SUBSTITUTED BY ', '', $update->fields->Text ?? '');
  }

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $pn = $this->getSubbedPrint($update);
    $bill = $update->id->basePrintNoStr ?? '';
    return ($pn && $bill) ? "Bill $bill was substituted by $pn." : '';
  }

  /**
   * {@inheritDoc}
   *
   * Adds the print number for the substitution bill.
   */
  public function doContext(object $update): array {
    return ['bill.substituted_by' => $this->getSubbedPrint($update)];
  }

}
