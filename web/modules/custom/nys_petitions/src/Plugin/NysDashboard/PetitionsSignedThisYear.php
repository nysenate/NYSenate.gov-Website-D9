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
 *   url = "/petitions",
 *   weight = 10
 * )
 */
class PetitionsSignedThisYear extends OverviewStatBase {

  /**
   * {@inheritDoc}
   *
   * Can't use entity traversal here, so manual query it is.
   *
   * @todo See if there is a better way to do this.
   *
   * A count of flagging entries, for which:
   *   - the flag type is 'sign_petition',
   *   - the creation date is after the beginning of the year,
   *   - the flagged entity is owned by the passed senator.
   */
  protected function buildContent(TermInterface $senator): ?string {

    $soy = mktime(0, 0, 0, 1, 1, date('Y'));
    try {
      $query = $this->database->select('flagging', 'f');
      $query->join('node__field_senator_multiref', 'sm', 'f.entity_id=sm.entity_id');
      $ret = $query
        ->condition('sm.bundle', 'petition')
        ->condition('sm.field_senator_multiref_target_id', $senator->id())
        ->condition('f.flag_id', 'sign_petition')
        ->condition('f.created', $soy, '>=')
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
