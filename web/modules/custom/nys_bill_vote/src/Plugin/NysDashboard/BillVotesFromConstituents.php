<?php

namespace Drupal\nys_bill_vote\Plugin\NysDashboard;

use Drupal\nys_senators\OverviewStatBase;
use Drupal\taxonomy\TermInterface;

/**
 * How many bill votes have been submitted from constituents.
 *
 * @OverviewStat(
 *   id = "constituent_votes",
 *   label = @Translation("Bill Activity"),
 *   description = @Translation("Votes from Constituents"),
 *   weight = 20
 * )
 */
class BillVotesFromConstituents extends OverviewStatBase {

  /**
   * {@inheritDoc}
   */
  protected function buildContent(TermInterface $senator): string {
    return '454';
  }

}
