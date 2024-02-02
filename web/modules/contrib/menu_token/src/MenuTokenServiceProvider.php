<?php

namespace Drupal\menu_token;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class MyModuleServiceProvider.
 *
 * @package Drupal\mymodule
 */
class MenuTokenServiceProvider extends ServiceProviderBase {

  /**
   * Override menu.link_tree service.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   Dep container.
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.link_tree');
    $definition->setClass('Drupal\menu_token\Service\MenuLinkTreeMenuToken');
    $definition->setArguments(
      [
        new Reference('menu.tree_storage'),
        new Reference('plugin.manager.menu.link'),
        new Reference('router.route_provider'),
        new Reference('menu.active_trail'),
        new Reference('controller_resolver'),
      ]
    );
  }

}
