<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\file_mdm\Plugin\Annotation\FileMetadata as FileMetadataAnnotation;

/**
 * Plugin manager for FileMetadata plugins.
 */
class FileMetadataPluginManager extends DefaultPluginManager implements FileMetadataPluginManagerInterface {

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache,
    ModuleHandlerInterface $module_handler,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct('Plugin/FileMetadata', $namespaces, $module_handler, FileMetadataPluginInterface::class, FileMetadataAnnotation::class);
    $this->alterInfo('file_metadata_plugin_info');
    $this->setCacheBackend($cache, 'file_metadata_plugins');
  }

  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $default_config = call_user_func($plugin_definition['class'] . '::defaultConfiguration');
    $configuration = $this->configFactory->get($plugin_definition['provider'] . '.file_metadata_plugin.' . $plugin_id)->get('configuration') ?: [];
    return parent::createInstance($plugin_id, NestedArray::mergeDeep($default_config, $configuration));
  }

}
