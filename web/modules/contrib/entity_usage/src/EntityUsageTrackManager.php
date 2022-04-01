<?php

namespace Drupal\entity_usage;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages entity_usage track plugins.
 */
class EntityUsageTrackManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityUsageTrackManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityUsage/Track', $namespaces, $module_handler, 'Drupal\entity_usage\EntityUsageTrackInterface', 'Drupal\entity_usage\Annotation\EntityUsageTrack');
    $this->alterInfo('entity_usage_track_info');
    $this->setCacheBackend($cache_backend, 'entity_usage_track_plugins');
  }

}
