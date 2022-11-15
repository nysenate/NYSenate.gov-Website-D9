<?php

namespace Drupal\nys_senators;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Collects "helper" functions for Senator entities.
 */
class SenatorsHelper {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The CacheBackend Interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructor class for Bills Helper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The backend cache.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache_backend;
  }

  /**
   * Defines a prefix for cache entries related to this class.
   */
  const CACHE_BIN_PREFIX = 'nys_senators';

  /**
   * Get the Cache.
   */
  protected function getCache(string $name): bool|object {
    return $this->cache->get(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Sets a value in the nys_bills cache.
   */
  protected function setCache(string $name, $value): void {
    $this->cache->set(static::CACHE_BIN_PREFIX . ':' . $name, $value);
  }

  /**
   * Removes a value from the nys_bills cache.
   */
  protected function removeCache(string $name): void {
    $this->cache->delete(static::CACHE_BIN_PREFIX . ':' . $name);
  }

  /**
   * Provides a reference to Drupal's storage service for taxonomy terms.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('taxonomy_term');
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
  public function getNameMapping(bool $active_only = TRUE, bool $refresh = FALSE): array {
    // Check the cache first.
    $cache_key = $active_only ? 'name_map:active_only' : 'name_map';
    $cache = $this->cache->get($cache_key);

    // If no cache, or a forced refresh, build the map array.
    if ($refresh || !$cache) {
      // Try to load the senators.
      try {
        $props = ['vid' => 'senator'];
        if ($active_only) {
          $props['field_active_senator'] = 1;
        }
        $senators = $this->getStorage()->loadByProperties($props);
      }
      catch (\Throwable) {
        $senators = [];
      }

      // Build the mapping array and cache it.
      $senator_mappings = [];
      foreach ($senators as $tid => $term) {
        $senator_mappings[$tid] = [
          'short_name' => $term->field_ol_shortname->value ?? '',
          'full_name' => $term->name->value ?? '',
        ];
      }
      $this->setCache($cache_key, $senator_mappings);
      return $senator_mappings;
    }

    return $cache->data;
  }

  /**
   * Loads a senator taxonomy term by OpenLeg member ID.
   */
  public function getSenatorTidFromMemberId($member_id): ? EntityInterface {
    try {
      $ret = $this->getStorage()
        ->loadByProperties(['field_ol_member_id' => $member_id]);
    }
    catch (\Throwable) {
      $ret = [];
    }
    return current($ret) ?: NULL;
  }

}
