<?php

namespace Drupal\nys_issues\Plugin\NysDashboard;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\nys_senators\ManagementPageBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Creates the overview page for the senator management dashboard.
 *
 * @SenatorManagementPage(
 *   id = "issues"
 * )
 */
class ManagementPageIssues extends ManagementPageBase {

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): array {

    return [
      '#theme' => 'nys_issues_management_issues',
      '#issues' => $this->getIssueCounts($senator),
      '#attached' => [
        'library' => [
          'nys_issues/nys_issues_management',
        ],
      ],
    ];

  }

  /**
   * Get the processed list of issues with follower counts.
   */
  protected function getIssueCounts(Term $senator): array {
    $rows = $this->queryIssueCounts($senator);
    usort($rows, fn($a, $b) => ($a['name'] === $b['name']) ? 0 : ($a['name'] < $b['name'] ? -1 : 1));
    foreach ($rows as &$row) {
      $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $row['tid']]);
      $row['canonical'] = Link::fromTextAndUrl($row['name'], $url)->toString();
    }
    return $rows;
  }

  /**
   * Query for a list of a issues with counts of in-district followers.
   *
   * For every issue, get a count of users following it, where the users are
   * assigned to the passed senator's district.
   */
  protected function queryIssueCounts(Term $senator): array {

    // Start from the issue taxonomy term.
    $query = $this->connection->select('taxonomy_term_field_data', 'tt');

    // Join to the flagging table.
    $query->join(
          'flagging', 'f',
          'f.entity_id=tt.tid AND f.entity_type=:taxonomy_term',
          [':taxonomy_term' => 'taxonomy_term']
      );

    // Join for the user's district.
    $query->join('user__field_district', 'ufd', 'ufd.entity_id=f.uid');

    // Join to the user's district's senator.
    $query->join(
          'taxonomy_term__field_senator', 'ttfs',
          'ttfs.entity_id=ufd.field_district_target_id AND ttfs.bundle=:districts',
          [':districts' => 'districts']
      );

    // Add "normal" fields to select.
    $query->addField('tt', 'tid');
    $query->addField('tt', 'name');

    // Add the aggregate selections.
    $query->addExpression('COUNT(f.uid)', 'total');

    // Add the predicates and grouping.
    $query->condition('tt.vid', 'issues')
      ->condition('ttfs.field_senator_target_id', $senator->id())
      ->groupBy('tt.tid')
      ->groupBy('tt.name');

    // Execute the query.
    try {
      $rows = $query->execute()->fetchAllAssoc('tid', \PDO::FETCH_ASSOC) ?? [];
      $this->logger->debug('Query for issues', ['@query' => (string) $query]);
    }
    catch (\Throwable $e) {
      $this->logger->error(
            'Query failed for issues follower count',
            ['@excp' => $e->getMessage(), '@query' => (string) $query]
        );
      $rows = [];
    }

    return $rows;

  }

}
