<?php

namespace Drupal\charts\Plugin\chart;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Chart Manager.
 *
 * Provides the Chart plugin plugin manager and manages discovery and
 * instantiation of chart plugins.
 */
class ChartManager extends DefaultPluginManager {

  /**
   * Constructor for ChartManager objects.
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
    parent::__construct('Plugin/chart', $namespaces, $module_handler, 'Drupal\charts\Plugin\chart\ChartInterface', 'Drupal\charts\Annotation\Chart');
  }

}
