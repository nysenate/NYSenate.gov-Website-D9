<?php

namespace Drupal\nys_petitions\Plugin\NysDashboard;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\nys_senators\ManagementPageBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Creates the petitions page for the senator management dashboard.
 *
 * @SenatorManagementPage(
 *   id = "petitions"
 * )
 */
class ManagementPagePetitions extends ManagementPageBase {

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): array {

    return [
      '#theme' => 'nys_petitions_management_petitions',
      '#your_petitions' => $this->getYourPetitions($senator),
      '#other_petitions' => $this->getOtherPetitions($senator),
      '#attached' => [
        'library' => [
          'nys_petitions/nys_petitions_management',
          'nysenate_theme/tabs',
          'nysenate_theme/highcharts',
        ],
      ],
    ];

  }

  /**
   * Creates a query with the required joins to users, districts, and flags.
   *
   * The query's tables are:
   *   n: node_field_data, the petition's node record
   *   smr: field_senator_multiref, where entity_id is the node id
   *   ttfs: field_senator, where entity_id is the district tid
   *   f: flagging
   *   ufd: field_district, where entity_id is the user id.
   */
  protected function createQuery(): SelectInterface {
    // Start from the node.
    $query = $this->connection->select('node_field_data', 'n');

    // Join for the node's owning senator.
    // smr.field_senator_multiref_target_id = $senator->id()
    $query->join('node__field_senator_multiref', 'smr', 'smr.entity_id=n.nid AND smr.bundle=n.type');

    // Join for the owning senator's district.
    // ttfs.entity_id = tid of the district to which $senator is assigned.
    $query->join(
          'taxonomy_term__field_senator', 'ttfs',
          'ttfs.field_senator_target_id=smr.field_senator_multiref_target_id AND ttfs.bundle=:term_bundle',
          [':term_bundle' => 'districts']
      );

    // Join the flagging table.
    $query->join(
          'flagging', 'f',
          'f.flag_id=:sign_flag AND f.entity_type=:node_type AND f.entity_id=n.nid',
          [':sign_flag' => 'sign_petition', ':node_type' => 'node']
      );

    // Join the signing user's district.
    // ufd.entity_id = tid of the district to which the user is assigned.
    $query->join('user__field_district', 'ufd', 'ufd.entity_id=f.uid');

    // Only looking for petitions.
    $query->condition('n.type', 'petition');

    return $query;
  }

  /**
   * Query for a list of a senator's petitions.
   *
   * For petitions owned by the passed senator, get a count of:
   *   - all signatures,
   *   - signatures from users in the passed senator's district,
   *   - signatures from users not in the passed senator's district,
   */
  protected function getYourPetitions(Term $senator): array {

    $query = $this->createQuery();

    // Add "normal" fields to select, the groupings, and the filters.
    $query->fields('n', ['nid', 'title', 'created'])
      ->groupBy('n.nid')
      ->groupBy('n.title')
      ->groupBy('n.created');

    // Add the aggregate selections.
    $query->addExpression('COUNT(f.id)', 'total');
    $query->addExpression('SUM(IF(ttfs.entity_id=ufd.field_district_target_id, 1, 0))', 'in_district');
    $query->addExpression('SUM(IF(ttfs.entity_id=ufd.field_district_target_id, 0, 1))', 'out_district');

    // Add the predicates and grouping.
    $query->condition('smr.field_senator_multiref_target_id', $senator->id());

    // Execute the query.
    try {
      $ret = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC) ?? [];
      $this->logger->debug(
            'Query for senator petitions',
            ['@query' => (string) $query]
        );
    }
    catch (\Throwable $e) {
      $this->logger->error(
            'Query failed for your petitions',
            ['@query' => (string) $query, '@excp' => $e->getMessage()]
        );
      $ret = [];
    }
    return $ret;

  }

  /**
   * Query for a list of "not this senator"'s petitions.
   *
   * The questionnaire content type's machine name is "webform".  For all
   * webforms not owned by the passed senator, get a count of:
   *   - responses from users in the passed senator's district,
   * Where a response is a webform_submission entry which:
   *   - is for a webform owned by a node of type "webform",
   *   - is for a webform not owned by a node assigned to the passed senator,
   *   - the submitting user is in the passed senator's district.
   */
  protected function getOtherPetitions(Term $senator): array {

    $query = $this->createQuery();

    // Add joins for the signing user's district.
    $query->join('taxonomy_term__field_senator', 'uttfs', 'uttfs.entity_id=ufd.field_district_target_id');

    // Add joins for owning senator's meta data.
    // fsn.field_senator_name_family = the last name of the node owner.
    $query->join('taxonomy_term__field_senator_name', 'fsn', 'fsn.entity_id=smr.field_senator_multiref_target_id');

    // Add "normal" fields to select, the groupings, and the filters.
    $query->fields('n', ['nid', 'title', 'created'])
      ->fields('fsn', ['field_senator_name_family'])
      ->condition('smr.field_senator_multiref_target_id', $senator->id(), '<>')
      ->condition('uttfs.field_senator_target_id', $senator->id())
      ->groupBy('n.nid')
      ->groupBy('n.title')
      ->groupBy('n.created')
      ->groupBy('fsn.field_senator_name_family');

    // Add the aggregate selections.
    $query->addExpression('COUNT(f.id)', 'in_district');

    // Execute the query.
    try {
      $rows = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC) ?? [];
      $this->logger->debug(
            'Query for other petitions',
            ['@query' => (string) $query]
        );
    }
    catch (\Throwable $e) {
      $this->logger->error(
            'Query failed for other petitions',
            ['@query' => (string) $query, '@excp' => $e->getMessage()]
        );
      $rows = [];
    }

    $ret = [];
    foreach ($rows as $row) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $row['nid']]);
      $link = Link::fromTextAndUrl($row['title'], $url)->toString();
      $node = Node::load($row['nid']);
      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

      $issues = '';
      $tids = [];
      if ($node->hasField('field_issues') && !$node->get('field_issues')->isEmpty()) {
        $node->field_issues->entity;
        foreach ($node->field_issues as $key => $value) {
          $term = $value->entity;
          $issue_link = Link::fromTextAndUrl($term->getName(), $term->toUrl())->toString();

          if ($key == count($node->field_issues) - 1) {
            $issues .= $issue_link;
          }
          else {
            $issues .= $issue_link . ', ';
          }

          $tids[] = '&f[' . ($key + 1) . ']=im_field_issues%3A' . $term->id();
        }
      }

      $author = '';
      if ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) {
        /**
         * @var \Drupal\taxonomy\Entity\Term $senator
         */
        $senator = $node->field_senator_multiref->entity;
        if ($senator) {
          $author = Link::fromTextAndUrl($senator->getName(), $senator->toUrl())->toString();
        }
      }

      $ret[] = [
        'data' => [
        [
          'data' => new FormattableMarkup(
            '<h3 class="entry-title">' . $link . '</h3>
              <div class="pet-type">' . $issues . '</div>
              <div class="author"><span>By: ' . $author . '</span></div>',
            []
          ),
          'class' => 'petition-link',
        ],
        ['data' => $row['in_district'], 'class' => 'signing-count'],
        ],
        'class' => ['other-petition'],
        'data-nid' => $row['nid'],
      ];
    }

    return [
      '#theme' => 'table',
      '#header' => [
        'Petition',
        new FormattableMarkup("Signatures From<br />Your District", []),
      ],
      '#rows' => $ret,
      '#empty' => 'No signatures were found',
      '#attributes' => [
        'width' => '100%',
        'class' => ['petition-signature-data'],
      ],
    ];

  }

}
