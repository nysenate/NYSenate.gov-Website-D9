<?php

namespace Drupal\nys_senators;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;

/**
 * Collects "helper" functions for Senator entities.
 */
class SenatorsHelper {

  /**
   * Defines a prefix for cache entries related to this class.
   */
  const CACHE_BIN_PREFIX = 'nys_senators';

  /**
   * Retrieves a cached value from nys_senators cache.
   *
   * @param string $name
   *   The key to retrieve.  Will be prefixed with CACHE_BIN_PREFIX.
   */
  protected static function getCache(string $name): object|bool {
    return \Drupal::cache()->get(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Sets a cache value inside the nys_senators cache.
   */
  protected static function setCache(string $name, $value): void {
    \Drupal::cache()->set(static::CACHE_BIN_PREFIX . ':' . $name, $value);
  }

  /**
   * Provides a reference to Drupal's storage service for taxonomy terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getStorage(): EntityStorageInterface {
    return \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  }

  /**
   * Retrieves a mapping of senator taxonomy term id to name data.
   *
   * @param bool $active_only
   *   If only the active senators should be included.
   * @param bool $refresh
   *   If a forced refresh should happen or not.
   *
   * @return array
   *   An array keyed by taxonomy term id.  Each element will have values for
   *   'short_name' and 'full_name'.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getNameMapping(bool $active_only = TRUE, bool $refresh = FALSE): array {
    // Check the cache first.
    $cache_key = $active_only ? 'name_map:active_only' : 'name_map';
    $mapping = static::getCache($cache_key);

    // If no cache, or a forced refresh, build the map array.
    if ($refresh || !$mapping) {
      // Try to load the senators.
      try {
        $props = ['vid' => 'senator'];
        if ($active_only) {
          $props['field_active_senator'] = 1;
        }
        $senators = static::getStorage()->loadByProperties($props);
      }
      catch (\Throwable) {
        $senators = [];
      }

      // Build the mapping array and cache it.
      $mapping = [];
      foreach ($senators as $tid => $term) {
        $mapping[$tid] = [
          'short_name' => $term->field_ol_shortname->value ?? '',
          'full_name' => $term->name->value ?? '',
        ];
      }
      static::setCache($cache_key, $mapping);
    }

    return $mapping;
  }

  /**
   * Loads a senator taxonomy term by OpenLeg member ID.
   */
  public function getSenatorTidFromMemberId(Node $node): ?EntityInterface {
    try {
      $ret = static::getStorage()
        ->loadByProperties(['field_ol_member_id' => $node->field_ol_member_id->value]);
    }
    catch (\Throwable) {
      $ret = [];
    }
    return current($ret) ?: NULL;
  }

}
