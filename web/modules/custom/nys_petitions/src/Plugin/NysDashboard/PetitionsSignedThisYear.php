<?php

namespace Drupal\nys_petitions\Plugin\NysDashboard;

use Drupal\nys_senators\OverviewStatBase;
use Drupal\taxonomy\TermInterface;

/**
 * How many petitions have been signed this year.
 *
 * @OverviewStat(
 *   id = "petitions_signed",
 *   label = @Translation("Petitions"),
 *   description = @Translation("New Signatures"),
 *   url = "/",
 *   weight = 10
 * )
 */
class PetitionsSignedThisYear extends OverviewStatBase {

  /**
   * {@inheritDoc}
   */
  protected function buildContent(TermInterface $senator): string {
    return '353';
  }

}
