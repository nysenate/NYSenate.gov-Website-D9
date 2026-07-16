<?php

namespace Drupal\nys_feeds\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin Manager for NysFeed plugins.
 */
class NysFeedPluginManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/NysFeed',
      $namespaces,
      $module_handler,
      'Drupal\nys_feeds\NysFeedPluginInterface',
      'Drupal\nys_feeds\Attribute\NysFeed',
    );
    $this->setCacheBackend($cache_backend, 'nys_feeds');
  }

}
