<?php

namespace Drupal\address_autocomplete\Routing;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for AddressProvider.
 */
class AddressProviderRoutes implements ContainerInjectionInterface {

  /**
   * @inheritDoc
   */
  protected $providerManager;

  /**
   * @inheritDoc
   */
  public function __construct(PluginManagerInterface $provider_manager) {
    $this->providerManager = $provider_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address_provider')
    );
  }

  /**
   * @inheritDoc
   */
  public function routes() {
    $pluginDefinitions = $this->providerManager->getDefinitions();

    $routes = [];

    foreach ($pluginDefinitions as $id => $pluginDefinition) {
      $pluginUrl = str_replace('_', '-', $pluginDefinition['id']);
      $routes["address_autocomplete.address_provider.$id"] = new Route(
        "admin/config/address-autocomplete/$pluginUrl",
        [
          '_form' => '\Drupal\address_autocomplete\Form\ProviderSettingsForm',
          '_title' => 'Configure ' . $pluginDefinition['label'],
          '_plugin_id' => $id,
        ],
        [
          '_permission' => 'access administration pages',
        ]
      );
    }

    return $routes;
  }

}
