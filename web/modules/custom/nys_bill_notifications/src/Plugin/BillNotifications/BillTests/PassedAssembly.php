<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for passing assembly.
 *
 * @BillTest(
 *   id = "passed_assembly",
 *   label = @Translation("Passed Assembly"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "PASSED_ASSEMBLY",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "PASSED_ASSEMBLY",
 *     },
 *   },
 *   priority = 11
 * )
 */
class PassedAssembly extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has passed the Assembly.";
  }

}
