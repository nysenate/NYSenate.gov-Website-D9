<?php

namespace Drupal\nys_bills;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Helper class for nys_bills module.
 *
 * @todo Bills should be a custom entity.  Most methods in this class will be
 *   part of that new class.
 */
class BillsHelper {

  /**
   * Defines a prefix for cache entries related to this class.
   */
  const CACHE_BIN_PREFIX = 'nys_bills';

  /**
   * Retrieves a value from the nys_bills cache.
   */
  protected static function getCache(string $name): bool|object {
    return \Drupal::cache()->get(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Sets a value in the nys_bills cache.
   */
  protected static function setCache(string $name, $value): void {
    \Drupal::cache()->set(static::CACHE_BIN_PREFIX . ':' . $name, $value);
  }

  /**
   * Removes a value from the nys_bills cache.
   */
  protected static function removeCache(string $name): void {
    \Drupal::cache()->delete(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Provides a reference to Drupal's storage service for taxonomy terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getStorage(): EntityStorageInterface {
    return \Drupal::entityTypeManager()->getStorage('node');
  }

  /**
   * Validates that a node is a bill or resolution.
   */
  protected static function isBill(Node $node): bool {
    return in_array($node->bundle(), ['bill', 'resolution']);
  }

  /**
   * Builds the legislative alias for the active version of a bill.
   *
   * @return string
   *   '/legislation/bills/<session>/<base_print>', with no version.
   *   Returns an empty string if $node is not a bill node.
   */
  public static function buildActiveAlias(Node $node): string {
    $ret = '';
    if (static::isBill($node)) {
      $session = $node->field_ol_session->value ?? '';
      $base = strtoupper($node->field_ol_base_print_no->value ?? '');
      if ($session && $base) {
        $ret = '/legislation/bills/' . $session . '/' . $base;
      }
    }
    return $ret;
  }

  /**
   * Builds the legislation alias for a bill node.
   *
   * @return string
   *   '/legislation/bills/<session>/<base_print>/amendment/<version>', where
   *   version could be the word 'original', or a single letter.  Returns an
   *   empty string if $node is not a bill node.
   */
  public static function buildAlias(Node $node): string {
    $version = strtoupper($node->field_ol_version->value ?? '') ?: 'original';
    return static::isBill($node)
      ? static::buildActiveAlias($node) . '/amendment/' . $version
      : '';
  }

  /**
   * Loads all bills related to a specified print number and session year.
   *
   * @param string $base_print
   *   The base print number (i.e., no version marker) of a bill or resolution.
   * @param string $session
   *   A session year.
   *
   * @return array
   *   An empty array of failure, otherwise as returned from loadMultiple().
   */
  public static function loadBillVersions(string $base_print, string $session): array {
    try {
      $storage = static::getStorage();
      $results = $storage->getQuery()
        ->condition('type', ['bill', 'resolution'], 'IN')
        ->condition('field_ol_base_print_no', $base_print)
        ->condition('field_ol_session', $session)
        ->execute();
      $bills = $storage->loadMultiple($results);
    }
    catch (\Throwable) {
      $bills = [];
    }

    return $bills;

  }

  /**
   * Generates a cache key for the versions of a base print.
   *
   * Note that the returned key lacks the static::CACHE_BIN_PREFIX.
   */
  public static function generateBillVersionCacheKey(Node $node): string {
    if (!static::isBill($node)) {
      throw new \InvalidArgumentException('Node must be a bill or resolution');
    }
    $node_type = $node->bundle();
    $base_print = $node->field_ol_base_print_no->value ?? '';
    $session = $node->field_ol_session->value ?? '';

    // A quick sanity check.
    if (!($session && $base_print)) {
      throw new \InvalidArgumentException('Invalid print number or session');
    }

    // Generate the key to be used.
    return implode(':', [
      'versions',
      $node_type,
      $session,
      $base_print,
    ]);
  }

  /**
   * Finds all amendments for a passed bill/resolution node.
   *
   * @return array
   *   In the form ['<bill_title>' => <node_id>, ...]
   *   While technically possible for the return to be an empty array, that
   *   is indicative of an error condition; the return should include (at
   *   least) the passed node's information.
   */
  public static function getBillVersions(Node $node): array {
    try {
      $cid = static::generateBillVersionCacheKey($node);
      $ret = static::getCache($cid)->data ?? NULL;
    }
    catch (\Throwable) {
      $ret = [];
      $cid = '';
    }

    if (is_null($ret) && $cid) {
      $ret = [];
      $base_print = $node->field_ol_base_print_no->value;
      $session = $node->field_ol_session->value;
      /** @var \Drupal\node\Entity\Node $bill */
      foreach (static::loadBillVersions($base_print, $session) as $bill) {
        $ret[$bill->getTitle()] = $bill->id();
      }
      static::setCache($cid, $ret);
    }

    return $ret;
  }

  /**
   * Wrapper to allow for loading by session and base print number.
   */
  public static function loadBillBySessionPrint(string $session, string $base_print, string $version = ''): ?Node {
    return static::loadBillByTitle(static::formatTitleParts($session, $base_print, $version));
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
  public static function loadBillByTitle(string $print_num): ?Node {
    try {
      $nodes = static::getStorage()->loadByProperties([
        'type' => 'bill',
        'title' => $print_num,
      ]);
      /** @var \Drupal\node\Entity\Node|NULL $ret */
      $ret = current($nodes) ?: NULL;
    }
    catch (\Throwable) {
      $ret = NULL;
    }
    return $ret;
  }

  /**
   * Clears caches for all amendments under a bill's base print number.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A bill or resolution node.
   */
  public static function clearBillVersionsCache(Node $node): void {
    if (static::isBill($node)) {

      // Clear the version lookup cache.
      static::removeCache(static::generateBillVersionCacheKey($node));

      // Clear the node cache for all versions.
      // E.g., if S100B gets updated, S100 and S100A are also invalidated.
      $tags = array_map(
        function ($nid) {
          return "node:$nid";
        },
        array_keys(static::getBillVersions($node))
      );
      if (count($tags)) {
        \Drupal::cache()->invalidateMultiple($tags);
      }

    }
  }

  /**
   * Formats the "press finish" title for a bill.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Full bill node object.
   *
   * @return string
   *   Returns a full bill name with Chamber + Bundle + BillName, or an empty
   *   string if $node is not a bill or resolution.
   */
  public static function formatFullBillTitle(Node $node): string {
    return self::isBill($node)
      ? ucfirst($node->field_ol_chamber->value) . ' ' . ucfirst($node->bundle()) . ' ' . $node->label()
      : '';
  }

  /**
   * Generates the standard-format title for a bill node.
   *
   * @see static::formatTitleParts()
   */
  public static function formatTitle(Node $node, string $version = '', string $separator = '-'): string {
    return !self::isBill($node)
      ? ''
      : static::formatTitleParts(
        $node->field_ol_session,
        $node->field_ol_base_print_no,
        $version,
        $separator
      );
  }

  /**
   * Generates the standard-format title, given a print number and session.
   *
   * @param string $session
   *   The bill's session year.
   * @param string $base_print
   *   The bill's base print number (i.e., no version marker)
   * @param string $version
   *   An optional version marker.  For the base print, leave blank.
   * @param string $separator
   *   Defaults to '-'.
   */
  public static function formatTitleParts(string $session, string $base_print, string $version = '', string $separator = '-'): string {
    return $session . $separator . strtoupper($base_print) . strtoupper($version);
  }

  /**
   * Given a bill/resolution node, returns the node of the active amendment.
   *
   * @return \Drupal\node\Entity\Node|null
   *   Returns NULL if multiple or no bills were found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadActiveVersion(Node $node): ?Node {
    $title = static::formatTitle($node, $node->field_ol_active_version->value);
    if ($node->getTitle() == $title) {
      $ret = $node;
    }
    else {
      $result = static::getStorage()
        ->loadByProperties(['title' => $title, 'type' => $node->bundle()]);
      $ret = current($result) ?: NULL;
    }
    return $ret;
  }

  /**
   * Standardizes the session year string for display.
   *
   * The odd-numbered year needs to be the first year in the legislative cycle
   * identifier in order to match Senate procedure.
   *
   * @param int $session_year
   *   A session year.
   *
   * @return string
   *   The legislative cycle, ready for display.
   */
  public function standardizeSession(int $session_year): string {
    if (($session_year % 2) > 0) {
      $ret = $session_year . '-' . ($session_year + 1);
    }
    else {
      $ret = ($session_year - 1) . '-' . $session_year;
    }
    return $ret;
  }

  /**
   * Audits URL aliases (path_auto) for all amendments as a bill is saved.
   *
   * This is necessary because:
   *  - The original amendment's field_ol_version is NULL/empty, and needs to
   *    be replaced with 'original' in the alias,
   *  - The canonical URL for a bill (meaning, a URL with no version specified)
   *    must point to the active amendment, which can change.
   *
   * There is an edge case caused by the timing of LBDC updates when a new
   * amendment is published.  In this scenario, a bill will reference a new
   * amendment which has not yet been imported.  This typically manifests as
   * an amendment showing the '/node/xxx' URL instead of the alias, or a 404
   * or redirection response.  In we detect a case like this, we set the
   * canonical URL back to "original", per F.S., 2022-10-27.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node being saved.
   */
  public static function validateAliases(Node $node): void {
    if (static::isBill($node)) {
      // Get the session year, base print, and version.
      $session = $node->field_ol_session->value ?? '';
      $base_print = $node->field_ol_base_print_no->value ?? '';
      $version = $node->field_ol_version->value ?? '';
      $active = $node->field_ol_active_version->value ?? '';

      // The alias to the specific amendment (i.e., .../amendment/original)
      $alias = static::buildAlias($node);

      // The alias to the active amendment (/legislation/bills/2021/S100)
      $canon = static::buildActiveAlias($node);

      // The actual internal path, e.g., '/node/1234'.
      // The 'alias' option turns off AliasPathProcessor.  Yes, it is very
      // counter-intuitive, but it is what it is.
      // See path_alias\PathProcessor\AliasPathProcessor::processOutbound()
      try {
        $int_path = $node->toUrl('canonical', ['alias' => TRUE])->toString();
      }
      catch (\Throwable) {
        $int_path = '';
      }

      // Leave is anything is weird.
      if (!($session && $base_print && $alias && $canon && $int_path)) {
        return;
      }

      try {
        // Insert/update the specific alias ($path points to $alias)
        static::getPathAlias(['path' => $int_path, 'alias' => $alias])->save();

        // If this is the active version, set canonical also: $path -> $canon.
        if ($active == $version) {
          $existing = static::getPathAlias(['alias' => $canon]);
          $existing->setPath($int_path)->save();
        }
      }
      catch (\Throwable $e) {
        \Drupal::logger('nys_bills')
          ->error('BillsHelper was unable to create or update an alias', ['message' => $e->getMessage()]);
      }
    }
  }

  /**
   * Loads (or creates) a path alias entity.
   *
   * @param array $values
   *   Can contain keys for 'path' and/or 'alias'.
   *
   * @return \Drupal\path_alias\Entity\PathAlias|null
   *   If the entity system throws an exception, this method returns NULL.  If
   *   $values is an empty array, a newly-created PathAlias object is returned.
   *   If $values has 'path', 'alias', or 'both', but no matching alias is
   *   found, a new PathAlias is created (but not saved) from those values.  If
   *   a matching alias is found, it is returned as loaded.   *
   */
  protected static function getPathAlias(array $values = []): ?PathAlias {
    try {
      /** @var \Drupal\path_alias\PathAliasStorage $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    }
    catch (\Throwable) {
      return NULL;
    }
    $props = [];
    foreach (['path', 'alias'] as $field) {
      if ($values[$field] ?? '') {
        $props[$field] = $values[$field];
      }
    }
    if (!$props) {
      return $storage->create();
    }
    $entities = $storage->loadByProperties($values);
    if (!$entities) {
      return $storage->create($props);
    }
    else {
      return current($entities);
    }
  }

}
