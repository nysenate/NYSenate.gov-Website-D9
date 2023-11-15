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
 *   url = "/constituents",
 *   weight = 5
 * )
 */
class NewConstituents extends OverviewStatBase {

  /**
   * {@inheritDoc}
   *
   * A count of user accounts for whom:
   *   - the creation date is after the beginning of the year,
   *   - the assigned district is the passed senator's district.
   */
  protected function buildContent(TermInterface $senator): ?string {

    $soy = mktime(0, 0, 0, 1, 1, date('Y'));
    $district = $this->helper->loadDistrict($senator);
    try {
      $ret = $this->manager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $soy, '>=')
        ->condition('status', 1)
        ->condition('field_district.entity.tid', $district->id())
        ->count()
        ->execute() ?? 0;
    }
    catch (\Throwable) {
      $ret = NULL;
    }
    return $ret;
  }

}
