<?php

namespace Drupal\config_filter;

use Drupal\config_filter\Config\FilteredStorage;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider to swap out the config sync service.
 */
class ConfigFilterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('config.storage.sync')) {
      $original = $container->getDefinition('config.storage.sync');
      $id = 'config.storage.sync';
    }
    elseif ($container->hasDefinition('config.storage.staging')) {
      // For Drupal 8.
      $original = $container->getDefinition('config.storage.staging');
      $id = 'config.storage.staging';
    }
    else {
      throw new ServiceNotFoundException('config_filter.storage.original', 'config.storage.sync');
    }
    // Save the original service so that we can use it in the factory.
    $container->setDefinition('config_filter.storage.original', $original);

    $definition = new Definition(FilteredStorage::class);
    $definition->setPublic(TRUE);
    $definition->setFactory([new Reference('config_filter.storage_factory'), 'getSync']);
    $container->setDefinition($id, $definition);
  }

}
