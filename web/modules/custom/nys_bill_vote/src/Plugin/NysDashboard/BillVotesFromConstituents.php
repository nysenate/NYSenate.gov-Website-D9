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
 *   url = "/bills",
 *   weight = 20
 * )
 */
class BillVotesFromConstituents extends OverviewStatBase {

  /**
   * {@inheritDoc}
   *
   * Can't use entity traversal here, so manual query it is.
   *
   * @todo See if there is a better way to do this.
   *
   * A count of voting entries, for which:
   *   - the vote type is 'nys_bill_vote',
   *   - the bill voted upon is sponsored by the passed senator,
   *   - the voting user is assigned to the passed senator's district,
   *   - the vote was submitted after the beginning of the year.
   */
  protected function buildContent(TermInterface $senator): ?string {

    $soy = mktime(0, 0, 0, 1, 1, date('Y'));
    $district = $this->helper->loadDistrict($senator);

    try {
      $query = $this->database->select('votingapi_vote', 'v');
      $query->join('node__field_ol_sponsor', 'ols', 'v.entity_id=ols.entity_id');
      $query->join('user__field_district', 'fd', 'v.user_id=fd.entity_id');
      $ret = $query
        ->condition('ols.bundle', 'bill')
        ->condition('ols.field_ol_sponsor_target_id', $senator->id())
        ->condition('fd.field_district_target_id', $district->id())
        ->condition('v.type', 'nys_bill_vote')
        ->condition('v.timestamp', $soy, '>=')
        ->countQuery()
        ->execute()
        ->fetchField() ?? 0;
    }
    catch (\Throwable) {
      $ret = NULL;
    }
    return $ret;
  }

}
