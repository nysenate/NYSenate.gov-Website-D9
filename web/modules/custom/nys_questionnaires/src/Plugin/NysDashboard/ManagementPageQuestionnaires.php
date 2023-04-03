<?php

namespace Drupal\nys_questionnaires\Plugin\NysDashboard;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\nys_senators\ManagementPageBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Creates the overview page for the senator management dashboard.
 *
 * @SenatorManagementPage(
 *   id = "questionnaires"
 * )
 */
class ManagementPageQuestionnaires extends ManagementPageBase {

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): array {

    return [
      '#theme' => 'nys_questionnaires_management_questionnaires',
      '#your_questionnaires' => $this->getYourQuestionnaires($senator),
      '#other_questionnaires' => $this->getOtherQuestionnaires($senator),
      '#attached' => [
        'library' => [
          'nys_questionnaires/nys_questionnaires_management',
          'nysenate_theme/tabs',
          'nysenate_theme/highcharts',
        ],
      ],
    ];

  }

  /**
   * Query for a list of a senator's questionnaires.
   *
   * The questionnaire content type's machine name is "webform".  For all
   * webforms owned by the passed senator, get a count of:
   *   - all responses,
   *   - responses from users in the passed senator's district,
   *   - responses from users not in the passed senator's district,
   * Where a response is a webform_submission entry which:
   *   - is for a webform owned by a node of type "webform",
   *   - is for a webform owned by a node assigned to the passed senator.
   */
  protected function getYourQuestionnaires(Term $senator): array {

    // Start from the node.
    $query = $this->connection->select('node_field_data', 'n');

    // Join for the node's owning senator.
    // smr.field_senator_multiref_target_id = $senator->id()
    $query->join('node__field_senator_multiref', 'smr', 'smr.entity_id=n.nid AND smr.bundle=n.type');

    // Join the webform reference table.  Want only open questionnaires.
    // nw.webform_target_id = the id of the node's webform.
    $query->join('node__webform', 'nw',
      'nw.entity_id=n.nid AND nw.bundle=n.type AND nw.webform_status=:status',
      [':status' => 'open']);

    // Join the submissions table.  LEFT join because there may not
    // be any submissions.
    $query->leftJoin('webform_submission', 'ws', 'ws.webform_id=nw.webform_target_id');

    // Join for the owning senator's district.
    // ttfs.entity_id = tid of the district to which $senator is assigned.
    $query->join('taxonomy_term__field_senator', 'ttfs',
      'ttfs.field_senator_target_id=smr.field_senator_multiref_target_id AND ttfs.bundle=:term_bundle',
      [':term_bundle' => 'districts']
    );

    // Join the submitting user's district.  LEFT join because the user may
    // not have a district.
    // ufd.entity_id = tid of the district to which the user is assigned.
    $query->leftJoin('user__field_district', 'ufd', 'ufd.entity_id=ws.uid');

    // Add "normal" fields to select, the groupings, and the filters.
    $query->addField('n', 'nid', 'qid');
    $query->addField('n', 'title');
    $query->addField('nw', 'webform_target_id', 'webform_id');

    // Add the aggregate selections.
    $query->addExpression('COUNT(ws.sid)', 'total');
    $query->addExpression('SUM(IF(ttfs.entity_id=ufd.field_district_target_id, 1, 0))', 'in_district');
    $query->addExpression('SUM(IF(ttfs.entity_id=ufd.field_district_target_id OR ufd.field_district_target_id IS NULL, 0, 1))', 'out_district');

    // Add the predicates and grouping.
    $query->condition('n.type', 'webform')
      ->condition('smr.field_senator_multiref_target_id', $senator->id())
      ->groupBy('n.nid')
      ->groupBy('n.title')
      ->groupBy('nw.webform_target_id');

    // Execute the query.
    try {
      $ret = $query->execute()->fetchAllAssoc('qid', \PDO::FETCH_ASSOC) ?? [];
      $this->logger->debug('Query for senator questionnaires',
        ['@query' => (string) $query]);
    }
    catch (\Throwable $e) {
      $this->logger->error(
        'Query failed for your questionnaires',
        ['@query' => (string) $query, '@excp' => $e->getMessage()]
      );
      $ret = [];
    }
    return $ret;

  }

  /**
   * Query for a list of "not this senator"'s questionnaires.
   *
   * The questionnaire content type's machine name is "webform".  For all
   * webforms not owned by the passed senator, get a count of:
   *   - responses from users in the passed senator's district,
   * Where a response is a webform_submission entry which:
   *   - is for a webform owned by a node of type "webform",
   *   - is for a webform not owned by a node assigned to the passed senator,
   *   - the submitting user is in the passed senator's district.
   */
  protected function getOtherQuestionnaires(Term $senator): array {

    // Start from the node.
    $query = $this->connection->select('node_field_data', 'n');

    // Join for the node's owning senator.
    // smr.field_senator_multiref_target_id = tid for questionnaire owner.
    $query->join('node__field_senator_multiref', 'smr', 'smr.entity_id=n.nid AND smr.bundle=n.type');

    // Join the webform reference table.  Want only open questionnaires.
    // nw.webform_target_id = the node's webform id.
    $query->join('node__webform', 'nw',
      'nw.entity_id=n.nid AND nw.bundle=n.type AND nw.webform_status=:status',
      [':status' => 'open']);

    // Join the submissions table.
    $query->join('webform_submission', 'ws', 'ws.webform_id=nw.webform_target_id');

    // Join for the owning senator's shortname.
    // fsn.field_senator_name_family = the last name of the node owner.
    $query->join('taxonomy_term__field_senator_name', 'fsn', 'fsn.entity_id=smr.field_senator_multiref_target_id');

    // Join for the passed senator's district.  Generates a "constant".
    // ttfs.entity_id = tid of the district of passed senator.
    $query->join('taxonomy_term__field_senator', 'ttfs',
      'ttfs.field_senator_target_id=:senator_id AND ttfs.bundle=:term_bundle',
      [':senator_id' => $senator->id(), ':term_bundle' => 'districts']
    );

    // Join the submitting user's district.
    // ufd.entity_id = tid of the district to which the user is assigned.
    $query->join('user__field_district', 'ufd', 'ufd.entity_id=ws.uid AND ufd.field_district_target_id=ttfs.entity_id');

    // Add "normal" fields to select, the groupings, and the filters.
    $query
      ->fields('n', ['nid', 'title'])
      ->fields('ws', ['webform_id'])
      ->fields('fsn', ['field_senator_name_family'])
      ->condition('n.type', 'webform')
      ->condition('smr.field_senator_multiref_target_id', $senator->id(), '<>')
      ->groupBy('n.nid')
      ->groupBy('n.title')
      ->groupBy('ws.webform_id')
      ->groupBy('fsn.field_senator_name_family');

    // Add the aggregate selections.
    $query->addExpression('COUNT(ws.sid)', 'in_district');

    // Execute the query.
    try {
      $rows = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC) ?? [];
      $this->logger->debug('Query for other questionnaires',
        ['@query' => (string) $query]);
    }
    catch (\Throwable $e) {
      $this->logger->error(
        'Query failed for other questionnaires',
        ['@query' => (string) $query, '@excp' => $e->getMessage()]
      );
      $rows = [];
    }

    $ret = [];
    foreach ($rows as $row) {
      $url = Url::fromRoute('entity.webform.canonical', ['webform' => $row['nid']]);
      $link = Link::fromTextAndUrl($row['title'], $url)->toString();
      $ret[] = [
        'data' => [
          [
            'data' => new FormattableMarkup($link . '<br />(' . $row['field_senator_name_family'] . ')', []),
            'class' => 'questionnaire-link',
          ],
          ['data' => $row['in_district'], 'class' => 'questionnaire-sub-count'],
        ],
        'class' => ['other-questionnaire'],
        'data-qid' => $row['nid'],
      ];
    }

    return [
      '#theme' => 'table',
      '#header' => [
        'Questionnaire',
        new FormattableMarkup("Submissions From<br />Your District", []),
      ],
      '#rows' => $ret,
      '#empty' => 'No submissions were found',
      '#attributes' => [
        'width' => '100%',
        'class' => ['questionnaire-submission-data'],
      ],
    ];

  }

}
