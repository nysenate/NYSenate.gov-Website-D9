<?php

namespace Drupal\address_map_link;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Map link plugin manager.
 */
class MapLinkManager extends DefaultPluginManager {

  /**
   * Definitions options list.
   *
   * @var array
   */
  protected $definitionsOptionsList;

  /**
   * Constructor for MapLinkManager objects.
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
    parent::__construct('Plugin/MapLink', $namespaces, $module_handler, 'Drupal\address_map_link\MapLinkInterface', 'Drupal\address_map_link\Annotation\MapLink');

    $this->alterInfo('address_map_link_map_link_info');
    $this->setCacheBackend($cache_backend, 'address_map_link_map_link_plugins');
  }

  /**
   * Gets the definition of all plugins as an options list.
   *
   * @return array
   *   A sorted array of plugin definition names (empty array if no definitions
   *   were found). Keys are plugin IDs.
   */
  public function getDefinitionsOptionsList(): array {
    if (!isset($this->definitionsOptionsList)) {
      foreach ($this->getDefinitions() as $pluginDefinition) {
        $this->definitionsOptionsList[$pluginDefinition['id']] = $pluginDefinition['name'];
      }
      asort($this->definitionsOptionsList);
    }
    return $this->definitionsOptionsList;
  }

}
