<?php

namespace Drupal\nys_bill_notifications\Plugin\BillNotifications\BillTests;

use Drupal\nys_bill_notifications\BillTestBase;

/**
 * Bill notification test for being brought to a vote on the floor.
 *
 * @BillTest(
 *   id = "floor_vote",
 *   label = @Translation("Floor Vote"),
 *   description = @Translation("A BillTest plugin to detect an update event."),
 *   name = "FLOOR_VOTE",
 *   pattern = {
 *     "scope" = "Bill Amendment Vote Info",
 *     "action" = "Insert",
 *     "fields" = {
 *       "Vote Type" = "floor",
 *     },
 *   },
 *   disabled = true,
 *   priority = 12
 * )
 */
class FloorVote extends BillTestBase {

  /**
   * {@inheritDoc}
   */
  public function getSummary(object $update): string {
    return "Bill " . $update->id->basePrintNoStr . " came up for voting on the floor.";
  }

}
