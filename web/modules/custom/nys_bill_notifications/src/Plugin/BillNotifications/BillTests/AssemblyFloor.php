<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for going to the assembly floor.
 *
 * @BillTest(
 *   id = "assembly_floor",
 *   label = @Translation("Gone to the Assembly Floor"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "ASSEMBLY_FLOOR",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "ASSEMBLY_FLOOR",
 *     },
 *   },
 *   priority = 3
 * )
 */
class AssemblyFloor extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " has gone to the Assembly Floor.";
  }

}
