<?php

namespace Drupal\nys_bill_vote\Plugin\NysDashboard;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\nys_senators\ManagementPageBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Creates the bill page for the senator management dashboard.
 *
 * @SenatorManagementPage(
 *   id = "bills"
 * )
 */
class ManagementPageBills extends ManagementPageBase {

  public const BILL_SUMMARY_MAX_CHARS = 100;

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): array {

    return [
      '#theme' => 'nys_bill_vote_management_bills',
      '#sponsored_bills' => $this->getBillVotes($senator),
      '#bill_messages' => $this->getBillMessages($senator),
      '#attached' => [
        'library' => [
          'nys_bill_vote/nys_bill_vote_management',
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
   *   n: node_field_data, the bill's node record
   *   olsum: field_ol_summary, where entity_id is the bill's node id
   *   ols: field_ol_sponsor, where entity_id is the bill's node id
   *   ttfs: field_senator, where entity_id is a district tid
   *   v: votingapi_vote, where entity_id is the bill's node id
   *   ufd: field_district, where entity_id is the voting user's id
   *   issues: a derived table for a bill's related issues.
   *
   * Fields:
   *   nid: node id
   *   title: node title
   *   summary: an abridged bill summary text (first 100 characters)
   *   issues_tid: a comma-delimited list of issue taxonomy ids
   *   issues: a comma-delimited list of issue names
   *   in_district_nay: number of "nay" votes in the sponsor's district
   *   in_district_aye: number of "aye" votes in the sponsor's district
   *   out_district_nay: number of "nay" votes outside the sponsor's district
   *   out_district_aye: number of "aye" votes outside the sponsor's district
   */
  protected function createQuery(): SelectInterface {

    // Start from the node.  Add the summary and sponsor fields.
    $query = $this->connection->select('node_field_data', 'n');
    $query->join(
          'node__field_ol_summary', 'olsum',
          'n.nid=olsum.entity_id AND olsum.bundle=:bill_bundle',
          [':bill_bundle' => 'bill']
      );
    $query->join(
          'node__field_ol_sponsor', 'ols',
          'n.nid=ols.entity_id AND ols.bundle=:bill_bundle',
          [':bill_bundle' => 'bill']
      );

    // Join the districts' senator field.
    $query->leftJoin(
          'taxonomy_term__field_senator', 'ttfs',
          'ttfs.field_senator_target_id=ols.field_ol_sponsor_target_id AND ttfs.bundle=:district_bundle',
          [':district_bundle' => 'districts']
      );

    // Join the voting table.
    $query->leftJoin(
          'votingapi_vote', 'v',
          'v.entity_id=n.nid AND v.entity_type=:node_type AND v.`type`=:vote_type',
          [':node_type' => 'node', ':vote_type' => 'nys_bill_vote']
      );

    // Join the users' district field.
    $query->leftJoin('user__field_district', 'ufd', 'ufd.entity_id=v.user_id');

    // Create the sub-query for the bills' related issues.
    $sub_query = $this->connection->select('taxonomy_term_field_data', 'td');
    $sub_query->join('node__field_issues', 'fi', 'td.tid=fi.field_issues_target_id');
    $sub_query->addField('fi', 'entity_id', 'nid');
    $sub_query->addExpression('GROUP_CONCAT(td.tid)', 'issues_tid');
    $sub_query->addExpression('GROUP_CONCAT(td.name)', 'issues');
    $sub_query->condition('fi.bundle', 'bill');
    $sub_query->groupBy('fi.entity_id');
    $query->leftJoin($sub_query, 'issues', 'issues.nid=n.nid');

    // Add the fields and expressions.
    $max = self::BILL_SUMMARY_MAX_CHARS;
    $query->fields('n', ['nid', 'title']);
    // The abridged summary.
    $expr = 'CONCAT(SUBSTRING(olsum.field_ol_summary_value,1,' . $max . '),' .
        'IF(LENGTH(olsum.field_ol_summary_value) > ' . $max . ", '...', ''))";
    $query->addExpression($expr, 'summary');
    // The lists of issues and their taxonomy ids.
    $query->addExpression("COALESCE(issues.issues_tid, '')", 'issues_tid');
    $query->addExpression("COALESCE(issues.issues, '')", 'issues');
    // Ayes and nays for in and out of district.
    $expr = 'SUM(IF(v.value=%u AND ufd.field_district_target_id%sttfs.entity_id, 1, 0))';
    $query->addExpression(sprintf($expr, 0, '='), 'in_district_nay');
    $query->addExpression(sprintf($expr, 1, '='), 'in_district_aye');
    $query->addExpression(sprintf($expr, 0, '<>'), 'out_district_nay');
    $query->addExpression(sprintf($expr, 1, '<>'), 'out_district_aye');

    // Add the grouping.
    $query->groupBy('n.nid')->groupBy('n.title')->groupBy('summary');

    return $query;
  }

  /**
   * Query for a list of a senator's bill_vote.
   *
   * For bill_vote owned by the passed senator, get a count of:
   *   - all signatures,
   *   - signatures from users in the passed senator's district,
   *   - signatures from users not in the passed senator's district,
   */
  protected function getBillVotes(Term $senator): array {

    $query = $this->createQuery();

    // Add the senator filter.
    $query->condition('ols.field_ol_sponsor_target_id', $senator->id());

    // Execute the query.
    try {
      $ret = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC) ?? [];
      $this->logger->debug(
            'Query for sponsored bills',
            ['@query' => (string) $query]
        );
    }
    catch (\Throwable $e) {
      $this->logger->error(
            'Query failed for sponsored bills',
            ['@query' => (string) $query, '@excp' => $e->getMessage()]
        );
      $ret = [];
    }
    return $ret;

  }

  /**
   * Query for all constituent messages tagged will a bill context.
   */
  protected function getBillMessages(Term $senator): array {
    return [
      '#markup' => '<h2>Coming Soon!</h2>',
    ];

  }

}
