<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SitemapTypeManager
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType
 */
class SitemapTypeManager extends DefaultPluginManager {

  /**
   * SitemapTypeManager constructor.
   * @param \Traversable $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/simple_sitemap/SitemapType',
      $namespaces,
      $module_handler,
      'Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeInterface',
      'Drupal\simple_sitemap\Annotation\SitemapType'
    );

    $this->alterInfo('simple_sitemap_sitemap_types');
    $this->setCacheBackend($cache_backend, 'simple_sitemap:sitemap_type');
  }
}
