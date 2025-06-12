<?php

namespace Drupal\nys_bill_vote\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to expose bill voting statistics.
 */
class BillVoteStatistics implements ContainerInjectionInterface {

  /**
   * Drupal's Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Caches the results for a given bill_id:district_id.
   *
   * @var array
   */
  protected array $cacheResults = [];

  /**
   * Constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      database: $container->get('database')
    );
  }

  /**
   * Builds the query used for compiling voting statistics.
   *
   * @param int $bill_id
   *   A node id.  This is expecting a bill node, but the type is not enforced.
   * @param int|null $district_id
   *   An optional district taxonomy id, used to differentiate in- and
   *   out-of-district voting stats.
   *
   * @return string
   *   A SQL query.
   */
  public function getStatsQuery(int $bill_id, ?int $district_id = NULL): string {
    $district_id = (int) $district_id ?: 'null';
    return <<<statsQuery
SELECT n.title, n.nid, 
        COUNT(IF(v.value=1, 1, NULL)) AS 'aye',
        COUNT(IF(v.value=1 AND fd.field_district_target_id=$district_id, 1, NULL)) AS 'aye_in_district',
        COUNT(IF(v.value=1 AND (fd.field_district_target_id IS NULL OR fd.field_district_target_id<>$district_id), 1, NULL)) AS 'aye_out_district',
        COUNT(IF(v.value=0,1,NULL)) AS 'nay',
        COUNT(IF(v.value=0 AND fd.field_district_target_id=$district_id, 1, NULL)) AS 'nay_in_district',
        COUNT(IF(v.value=0 AND (fd.field_district_target_id IS NULL OR fd.field_district_target_id<>$district_id), 1, NULL)) AS 'nay_out_district',
        COUNT(*) as total,
        COUNT(IF(fd.field_district_target_id=$district_id, 1, NULL)) as 'total_in_district',
        COUNT(IF(fd.field_district_target_id IS NULL OR fd.field_district_target_id<>$district_id, 1, NULL)) AS 'total_out_district'
      FROM votingapi_vote v 
      INNER JOIN node_field_data n ON n.nid=v.entity_id 
      LEFT JOIN user__field_district fd ON fd.entity_id=v.user_id 
      WHERE v.entity_id=$bill_id 
      GROUP BY n.title, n.nid;
statsQuery;
  }

  /**
   * Retrieves a populated BillVoteStatistics object for a bill and district.
   *
   * @param int $bill_id
   *   A node id.  This is expecting a bill node, but the type is not enforced.
   * @param int|null $district_id
   *   An optional district taxonomy id, used to differentiate in- and
   *   out-of-district voting stats.
   * @param bool $refresh
   *   Indicates if cached results should be discarded, given a previously
   *   queried $bill_id:$district_id.
   *
   * @return array
   *   An array of voting statistics.  Example:
   *   [
   *     'title' => '2023-A10498',
   *     'nid' => '12039523',
   *     'aye' => '303',
   *     'aye_in_district' => '1',
   *     'aye_out_district' => '302',
   *     'nay' => '270',
   *     'nay_in_district' => '9',
   *     'nay_out_district' => '261',
   *     'total' => '573',
   *     'total_in_district' => '10',
   *     'total_out_district' => '563',
   *   ]
   */
  public function getStats(int $bill_id, ?int $district_id = NULL, bool $refresh = FALSE): array {
    if (!$bill_id) {
      throw new \InvalidArgumentException('Bill id cannot be empty.');
    }

    $cache_key = $bill_id . ':' . ((int) $district_id ?: 'null');

    if ($refresh || !array_key_exists($cache_key, $this->cacheResults)) {
      $results = $this->database
        ->query($this->getStatsQuery($bill_id, $district_id))
        ->fetchAssoc();
      $this->cacheResults[$cache_key] = $results + ['district_id' => $district_id];
    }

    return $this->cacheResults[$cache_key];
  }

}
