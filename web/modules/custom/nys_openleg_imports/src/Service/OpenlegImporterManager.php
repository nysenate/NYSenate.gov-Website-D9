<?php

namespace Drupal\nys_openleg_imports\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_openleg_imports\ImporterInterface;
use Drupal\nys_openleg_imports\ImportProcessorInterface;

/**
 * Management class for NYS Openleg Import plugins.
 */
class OpenlegImporterManager extends DefaultPluginManager {

  /**
   * The Openleg Import Processor service.
   *
   * @var \Drupal\nys_openleg_imports\Service\OpenlegImportProcessorManager
   */
  protected OpenlegImportProcessorManager $processorManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, OpenlegImportProcessorManager $processorManager) {
    parent::__construct(
          'Plugin/OpenlegImporter',
          $namespaces,
          $module_handler,
          'Drupal\nys_openleg_imports\ImporterInterface',
          'Drupal\nys_openleg_imports\Annotation\OpenlegImporter'
      );
    $this->processorManager = $processorManager;
    $this->setCacheBackend($cache_backend, 'openleg_imports.importers');
  }

  /**
   * Alias for createInstance() for import plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see DefaultPluginManager::createInstance()
   */
  public function getImporter(string $plugin_id, array $configuration = []): ImporterInterface {
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Alias for createInstance() for processor plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see DefaultPluginManager::createInstance()
   */
  public function getProcessor(string $plugin_id, array $configuration = []): ImportProcessorInterface {
    return $this->processorManager->getProcessor($plugin_id, $configuration);
  }

}
