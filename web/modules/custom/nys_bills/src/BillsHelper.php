<?php

namespace Drupal\nys_bills;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;

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

  /**
   * Helper function to clear the cache for certain cache entries.
   *
   * @param object $node
   *   A drupal node object.
   */
  public function clearBillVersionsCache($node): void {
    $session = $node->field_ol_session->value;
    $print_no = $node->field_ol_base_print_no->value;

    // Clear the cache for the 'previous version' query results as well
    // as the 'bill versions' query results.
    $cache_id_identifier = $session . '-' . Html::getClass($print_no);
    $bill_versions_cache = 'nys_bills-versions-bill-' . $cache_id_identifier;

    drupal_flush_all_caches();

    // If an amendment was created/updated we need to invalidate the
    // cache tag for the original bill, so the amendments section
    // displays correctly.
    $versions = $this->getBillVersions($node->bundle(), $print_no, $session);
    if (count($versions) > 1) {
      $tags = [];
      foreach ($versions as $version) {
        $tags[] = 'node:' . $version['nid'];
      }
      if (!empty($tags)) {
        Cache::invalidateTags($tags);
      }
    }
    \Drupal::logger('NYS Bills')->notice('Clear and invalidate the cache tag for the Bill');
  }

  /**
   * Helper function to grab full Bill name with chamber.
   *
   * @param object $node
   *   Full bill node object.
   *
   * @return string
   *   Returns a full bill name with Chamber + 'Bill' + BillName.
   */
  public function getFullBillName($node) {
    $chamber = ucfirst($node->field_ol_chamber->value);
    $title = $chamber . ' Bill ' . $node->label();
    return $title;
  }

  /**
   * Retrieves the active amendment, given a session year & print number.
   */
  public function getLoadActivefromSessionBasePrint($session, $baseprint) {
    $query = "SELECT n.nid FROM node n
      INNER JOIN node__field_ol_print_no pn
      ON n.nid = pn.entity_id AND pn.bundle = 'bill'
      INNER JOIN node__field_ol_session sess
      ON n.nid = sess.entity_id AND sess.bundle = 'bill'
      INNER JOIN node__field_ol_active_version fa
      ON n.nid = fa.entity_id AND fa.bundle = 'bill'
      INNER JOIN node__field_ol_base_print_no bpn
      ON n.nid = bpn.entity_id AND bpn.bundle = 'bill'
      WHERE sess.field_ol_session_value = :sess
      AND bpn.field_ol_base_print_no_value = :bpn
      AND CONCAT(bpn.field_ol_base_print_no_value,
      fa.field_ol_active_version_value) = pn.field_ol_print_no_value LIMIT 1";

    $args = [':sess' => $session, ':bpn' => $baseprint];

    $db_results = $this->connection->query($query, $args);
    return $db_results->fetchField();
  }

  /**
   * Function to Retrieves sponsor objects from information in a bill node.
   */
  public function resolveAmendmentSponsors($amendment, $chamber) {
    $ret = [];
    $cycle = ['co', 'multi'];
    $senators = $this->getSenatorNameMapping();
    foreach ($cycle as $type) {
      $ret[$type] = [];
      $propname = "field_ol_{$type}_sponsor_names";
      $language_none = LanguageInterface::LANGCODE_NOT_SPECIFIED;
      $sponsors = json_decode($amendment->{$propname}[$language_none][0]['value']);
      foreach ($sponsors as $one_sponsor) {
        switch ($chamber) {
          case 'senate':
            $termid = $this->getSenatorTidFromMemberId($one_sponsor->memberId);
            $ret[$type][] = (object) [
              'memberId' => $one_sponsor->memberId,
              'nodeId' => $termid,
              'fullName' => $senators[$termid]['full_name'],
            ];
            break;

          case 'assembly':
            $ret[$type][] = (object) [
              'memberId' => NULL,
              'nodeId' => NULL,
              'fullName' => $one_sponsor->fullName,
            ];
            break;
        }
      }
    }

    return $ret;
  }

  /**
   * Standardizes the session year string for display.
   *
   * The odd-numberedyear needs to be the first year in the
   * legislative cycle identifier in order to match Senate
   * procedure.
   *
   * If a non-integer is passed, the input is returned untouched.
   *
   * @param int $session_year
   *   A session year.
   *
   * @return string
   *   The legislative cycle, ready for display.
   */
  public function standardizeSession($session_year) {
    // Initialize the return with the input, in case a non-integer was passed.
    $ret = $session_year;
    if (is_int($session_year)) {
      // The odd numbered year needs to be the first year
      // in the legislative cycle to match Senate procedure.
      if (($session_year % 2) > 0) {
        $ret = $session_year . '-' . ($session_year + 1);
      }
      else {
        $ret = ($session_year - 1) . '-' . $session_year;
      }
    }
    return $ret;
  }

  /**
   * Returns a cached mapping of senator names, keyed by the nid.
   *
   * @see https://bitbucket.org/mediacurrent/nys_nysenate/src/develop/sites/all/modules/custom/nys_utils/nys_utils.module
   * function get_senator_name_mapping() from D7
   */
  public function getSenatorNameMapping() {
    $cache_service = \Drupal::cache();
    $cache_key = 'nys_utils_get_senator_name_mapping';
    $cache = $cache_service->get($cache_key);
    if (!$cache) {

      $senator_terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadByProperties([
        'vid' => 'senator',
      ]);

      $senator_mappings = [];
      foreach ($senator_terms as &$term) {
        $senator_mappings[$term->tid->value] = [
          'short_name' => $term->get('field_senator_name')[0]->given ?? '',
          'full_name' => $term->get('field_senator_name')[0]->title ?? '',
        ];
      }
      $cache_service->set($cache_key, $senator_mappings);
      // If data is cached, return cached data.
      if ($cache = \Drupal::cache()->get($cache_key)) {
        return $cache->data;
      }
    }
  }

  /**
   * Retrieves the senator node id associated with an OpenLeg member id.
   *
   * @param int $member_id
   *   Member id.
   *
   * @return int
   *   node_id
   *
   * @see https://bitbucket.org/mediacurrent/nys_nysenate/src/develop/sites/all/modules/custom/nys_utils/nys_utils.module
   * function nys_utils_get_senator_nid_from_member_id from D7
   */
  public function getSenatorTidFromMemberId($member_id) {
    $preloaded = &drupal_static(__FUNCTION__, []);

    if (!array_key_exists($member_id, $preloaded)) {
      $query = "SELECT entity_id FROM taxonomy_term__field_ol_member_id WHERE field_ol_member_id_value = :memberid";
      $queryargs = [
        ':memberid' => $member_id,
      ];
      $preloaded[$member_id] = $this->connection->query($query, $queryargs)->fetchField();
    }
    return $preloaded[$member_id];
  }

}
