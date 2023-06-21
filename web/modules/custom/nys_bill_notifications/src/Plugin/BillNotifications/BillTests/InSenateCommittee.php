<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being referred to a Senate committee.
 *
 * @BillTest(
 *   id = "in_senate_comm",
 *   label = @Translation("In Senate Committee"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "IN_SENATE_COMM",
 *   pattern = {
 *     "scope" = "Bill",
 *     "action" = "Insert",
 *     "fields" = {
 *       "Status" = "IN_SENATE_COMM",
 *       "Committee Name" = TRUE,
 *     },
 *   },
 *   priority = 2
 * )
 */
class InSenateCommittee extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    $comm = ucwords(strtolower($update->fields->{'Committee Name'}));
    return "Bill " . $update->id->basePrintNoStr . " was referred to " .
        $comm . " Senate committee.";
  }

}
