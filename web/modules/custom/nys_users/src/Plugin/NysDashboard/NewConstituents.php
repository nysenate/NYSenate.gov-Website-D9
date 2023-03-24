<?php

namespace Drupal\nys_users\Plugin\NysDashboard;

use Drupal\nys_senators\OverviewStatBase;
use Drupal\taxonomy\TermInterface;

/**
 * How many petitions have been signed this year.
 *
 * @OverviewStat(
 *   id = "new_users_in_district",
 *   label = @Translation("Sign Ups"),
 *   description = @Translation("New Constituents"),
 *   url = "/",
 *   weight = 5
 * )
 */
class NewConstituents extends OverviewStatBase {

  /**
   * {@inheritDoc}
   */
  protected function buildContent(TermInterface $senator): string {
    return '10';
  }

}
