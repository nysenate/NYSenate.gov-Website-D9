<?php

namespace Drupal\entityqueue;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an EntityQueueHandler plugin manager.
 */
class EntityQueueHandlerManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityQueueHandlerManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityQueueHandler', $namespaces, $module_handler, NULL, 'Drupal\entityqueue\Annotation\EntityQueueHandler');

    $this->setCacheBackend($cache_backend, 'entityqueuehandler');
  }

  /**
   * Gets all handlers.
   *
   * @return array
   *   Returns all entityqueue handlers.
   */
  public function getAllEntityQueueHandlers() {
    $handlers = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin_def) {
      $handlers[$plugin_id] = $plugin_def['title'];
    }
    asort($handlers);

    return $handlers;
  }

}
