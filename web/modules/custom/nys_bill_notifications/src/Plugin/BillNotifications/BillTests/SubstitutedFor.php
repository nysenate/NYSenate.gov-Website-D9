<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for substituting for another bill.
 *
 * @BillTest(
 *   id = "substituted_for",
 *   label = @Translation("Substituted For"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "SUBSTITUTE_FOR",
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
   * Parses update text to get the substituted bill print number.
   */
  protected function getSubbedPrint(object $update): string {
    return str_replace('SUBSTITUTED FOR ', '', $update->fields->Text ?? '');
  }

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $pn = $this->getSubbedPrint($update);
    $bill = $update->id->basePrintNoStr ?? '';
    return ($pn && $bill) ? "$bill was substituted for $pn." : '';
  }

  /**
   * {@inheritDoc}
   *
   * Adds the print number for the substituted bill.
   */
  public function doContext(object $update): array {
    return ['bill.same_as' => $this->getSubbedPrint($update)];
  }

}
