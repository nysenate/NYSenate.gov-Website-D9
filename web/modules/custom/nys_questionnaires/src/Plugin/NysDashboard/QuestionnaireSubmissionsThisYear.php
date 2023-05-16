<?php

namespace Drupal\nys_questionnaires\Plugin\NysDashboard;

use Drupal\nys_senators\OverviewStatBase;
use Drupal\taxonomy\TermInterface;

/**
 * How many questionnaire submissions have been submitted this year.
 *
 * @OverviewStat(
 *   id = "questionnaire_submissions",
 *   label = @Translation("Questionnaires"),
 *   description = @Translation("New Responses"),
 *   url = "/questionnaires",
 *   weight = 40
 * )
 */
class QuestionnaireSubmissionsThisYear extends OverviewStatBase {

  /**
   * {@inheritDoc}
   *
   * Can't use entity traversal here, so manual query it is.
   *
   * @todo See if entity traversal works with webform submissions.
   *
   * A count of all webform submissions which:
   *   - are owned by a node of type "webform",
   *   - are owned by a node assigned to the passed senator,
   *   - were submitted since the start of the calendar year.
   */
  protected function buildContent(TermInterface $senator): ?string {

    $soy = mktime(0, 0, 0, 1, 1, date('Y'));
    try {
      // Start from the node.
      $query = $this->database->select('node_field_data', 'n');

      // Join for the node's owning senator.
      $query->join('node__field_senator_multiref', 'smr', 'smr.entity_id=n.nid AND smr.bundle=n.type');

      // Join the webform reference and submissions tables.
      $query->join('node__webform', 'nw', 'nw.entity_id=n.nid AND nw.bundle=n.type');
      $query->join('webform_submission', 'ws', 'ws.webform_id=nw.webform_target_id');

      $ret = $query
        ->fields('ws', ['sid'])
        ->condition('n.type', 'webform')
        ->condition('smr.field_senator_multiref_target_id', $senator->id())
        ->condition('ws.created', $soy, '>=')
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
