<?php

namespace Drupal\multiline_config;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Multiline config customizations service provider implementation.
 */
class MultilineConfigServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Adds Config filter compatibility.
    if ($container->hasDefinition('config_filter.storage.original')) {
      $id = 'config_filter.storage.original';
    }
    elseif ($container->hasDefinition('config.storage.sync')) {
      $id = 'config.storage.sync';
    }
    elseif ($container->hasDefinition('config.storage.staging')) {
      $id = 'config.storage.staging';
    }

    if (!empty($id) && $config_storage = $container->getDefinition($id)) {
      $config_storage->setClass('Drupal\multiline_config\MultilineConfigFileStorage');
      $config_storage->setFactory('Drupal\multiline_config\MultilineConfigFileStorageFactory::getSync');
    }
  }

}

