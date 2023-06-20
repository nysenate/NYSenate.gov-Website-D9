<?php

namespace Drupal\nys_openleg_imports\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_openleg_imports\ImportProcessorInterface;

/**
 * Management class for NYS Openleg importer endpoints.
 */
class OpenlegImportProcessorManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
          'Plugin/OpenlegImportProcessor',
          $namespaces,
          $module_handler,
          'Drupal\nys_openleg_imports\ImportProcessorInterface',
          'Drupal\nys_openleg_imports\Annotation\OpenlegImportProcessor',
      );
    $this->setCacheBackend($cache_backend, 'openleg_imports.processors');
  }

  /**
   * Alias for createInstance() for import processor plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see DefaultPluginManager::createInstance()
   */
  public function getProcessor(string $plugin_id, array $configuration = []): ImportProcessorInterface {
    return $this->createInstance($plugin_id, $configuration);
  }

}
