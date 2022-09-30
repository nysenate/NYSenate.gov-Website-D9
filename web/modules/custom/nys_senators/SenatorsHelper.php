<?php

namespace Drupal\nys_senators;

use Drupal\Core\Entity\EntityStorageInterface;

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
  protected static function getCache(string $name) {
    return \Drupal::cache()->get(static::CACHE_BIN_PREFIX . '.' . $name);
  }

  /**
   * Sets a cache value inside the nys_senators cache.
   */
  protected static function setCache(string $name, $value) {
    \Drupal::cache()->set(static::CACHE_BIN_PREFIX . '.' . $name, $value);
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
   * Retrieves a mapping of taxonomy term id to name data.
   *
   * @param bool $refresh
   *   If a forced refresh should happen or not.
   *
   * @return array
   *   An array keyed by taxonomy term id.  Each element will have values for
   *   'short_name' and 'full_name'.
   */
  public static function getNameMapping(bool $refresh = FALSE): array {
    $mapping = static::getCache('name_map');
    if ($refresh || !$mapping) {
      $senators = static::getStorage()
        ->loadByProperties(['field_active_senator' => 1]);
      $mapping = [];
      foreach ($senators as $tid => $entity) {
        $mapping[$tid] = [
          'short_name' => $entity->field_short_name->value,
          'full_name' => $entity->field_senator_name->value,
        ];
      }
      static::setCache('name_map', $mapping);
    }
    return $mapping;
  }

}
