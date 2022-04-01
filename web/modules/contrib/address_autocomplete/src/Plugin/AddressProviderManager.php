<?php

namespace Drupal\address_autocomplete\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Address provider plugin manager.
 */
class AddressProviderManager extends DefaultPluginManager {
  
  /**
   * Constructs a new AddressProviderManager object.
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
    parent::__construct('Plugin/AddressProvider', $namespaces, $module_handler, 'Drupal\address_autocomplete\Plugin\AddressProviderInterface', 'Drupal\address_autocomplete\Annotation\AddressProvider');

    $this->alterInfo('address_autocomplete_address_provider_info');
    $this->setCacheBackend($cache_backend, 'address_autocomplete_address_provider_plugins');
  }

}
