<?php

namespace Drupal\nys_bills;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;

/**
 * Helper class for nys_bills module.
 */
class BillsHelper {

  /**
   * Default object for database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor class for Bills Helper.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Helper function to retrieved cached bills data.
   */
  public function getBillVersions($node_type, $bill_base_print_no, $bill_session_year) {
    $cid = 'nys_bills-versions-' .
      str_replace(' ', '', $node_type) . '-' .
      str_replace(' ', '', $bill_session_year) . '-' .
      str_replace(' ', '', $bill_base_print_no);

    // If data is cached, return cached data.
    if ($cache = \Drupal::cache()->get($cid)) {
      return $cache->data;
    }

    $results = [];
    if ($bill_base_print_no && $bill_session_year && $node_type) {
      $query = "SELECT n.title, n.nid, os.field_ol_session_value
        FROM node__field_ol_base_print_no pn
        JOIN node_field_data n
        ON n.nid = pn.entity_id
        JOIN node__field_ol_session os
        ON os.entity_id = pn.entity_id AND os.bundle = pn.bundle
        WHERE pn.field_ol_base_print_no_value = :base_print_no
        AND pn.bundle = :bundle AND os.field_ol_session_value = :session_year;";
      $queryargs = [
        ':base_print_no' => $bill_base_print_no,
        ':bundle' => $node_type,
        ':session_year' => $bill_session_year,
      ];

      $db_results = $this->connection->query($query, $queryargs);
      foreach ($db_results as $key => $r) {
        $results[] = ['nid' => $r->nid, 'title' => $r->title];
      }
    }

    // Cache data based on cache ID that was set above.
    \Drupal::cache()->set($cid, $results);
    return $results;
  }

  /**
   * Loads a bill Node by print number (title).
   *
   * @param string $print_num
   *   A bill print number, such as '2021-S123B'.
   *
   * @return \Drupal\node\Entity\Node|null
   *   If multiple or no bills are found, NULL is returned.
   */
  public static function loadBillByPrint(string $print_num): ?Node {
    try {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties(['type' => 'bill', 'title' => $print_num]);
      /** @var \Drupal\node\Entity\Node|NULL $ret */
      $ret = count($nodes) == 1 ? current($nodes) : NULL;
    }
    catch (\Throwable $e) {
      $ret = NULL;
    }
    return $ret;

  }

}
