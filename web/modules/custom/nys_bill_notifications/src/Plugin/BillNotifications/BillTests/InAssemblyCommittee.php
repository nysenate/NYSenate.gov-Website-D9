<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being referred to an Assembly committee.
 *
 * @BillTest(
 *   id = "in_assembly_comm",
 *   label = @Translation("In Assembly Committee"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "IN_ASSEMBLY_COMM",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Update",
 *     "fields" = {
 *       "Status" = "IN_ASSEMBLY_COMM",
 *       "Committee Name" = TRUE,
 *     },
 *   },
 *   priority = 1
 * )
 */
class InAssemblyCommittee extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $comm = ucwords(strtolower($update->fields->{'Committee Name'}));
    return "Bill " . $update->id->basePrintNoStr . " was referred to " .
        $comm . " Assembly committee.";
  }

}
