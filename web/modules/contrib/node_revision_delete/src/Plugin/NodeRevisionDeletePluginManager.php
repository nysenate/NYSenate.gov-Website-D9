<?php

namespace Drupal\node_revision_delete\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the node revision delete plugin manager.
 */
class NodeRevisionDeletePluginManager extends DefaultPluginManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Statically cached array of plugin settings per node type.
   *
   * @var array
   *   Array of merged settings from config and node type third party settings.
   */
  protected array $settings = [];

  /**
   * Constructs a new NodeRevisionDeletePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/NodeRevisionDelete', $namespaces, $module_handler, 'Drupal\node_revision_delete\Plugin\NodeRevisionDeleteInterface', 'Drupal\node_revision_delete\Annotation\NodeRevisionDelete');
    $this->alterInfo('node_revision_delete_info');
    $this->setCacheBackend($cache_backend, 'node_revision_delete_plugins');
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Retrieve the configured plugin instance.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param array|null $configuration
   *   Optional provided configuration to use.
   *
   * @return \Drupal\node_revision_delete\Plugin\NodeRevisionDeleteInterface
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPlugin(string $plugin_id, array $configuration = NULL): NodeRevisionDeleteInterface {
    return $this->createInstance($plugin_id, $configuration ?? $this->getDefaultPluginSettings($plugin_id)['settings'] ?? []);
  }

  /**
   * Gets the default settings for a plugin.
   *
   * @param string $plugin_id
   *   The plugin ID to get the settings for.
   *
   * @return array
   *   The settings for the plugin and node type.
   */
  public function getDefaultPluginSettings(string $plugin_id): array {
    $configuration = $this->configFactory->get('node_revision_delete.settings');
    return $configuration->get('defaults')[$plugin_id] ?? [];
  }

  /**
   * Gets the settings for a plugin and node type.
   *
   * @param string $plugin_id
   *   The plugin ID to get the settings for.
   * @param string $node_type
   *   The node type to get the settings for.
   *
   * @return array
   *   The settings for the plugin and node type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSettings(string $plugin_id, string $node_type): array {
    $settings = $this->getAllSettings();
    return $settings[$node_type]['plugin'][$plugin_id] ?? [];
  }

  /**
   * Gets the settings for a node type.
   *
   * @param string $node_type
   *   The node type id.
   *
   * @return array
   *   The settings for the node type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSettingsNodeType(string $node_type): array {
    $settings = $this->getAllSettings();
    return $settings[$node_type] ?? [];
  }

  /**
   * Retrieves all node types settings with all plugins settings overrides.
   *
   * @return array
   *   An array of settings, keyed by node-type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAllSettings(): array {
    if ($this->settings) {
      return $this->settings;
    }

    $defaults = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin_definition) {
      $defaults[$plugin_id] = $this->getDefaultPluginSettings($plugin_id);
    }

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      // Node type overrides are merged with defaults. Filter out any settings
      // which do not map to any plugin id.
      $overrides = array_intersect_key($node_type->getThirdPartySettings('node_revision_delete'), $defaults);
      $this->settings[$node_type->id()]['plugin'] = array_merge($defaults, $overrides);
      $this->settings[$node_type->id()]['status'] = empty($overrides) ? 'default' : 'overridden';
    }

    return $this->settings;
  }

  /**
   * Resets the static cache for the plugin settings.
   */
  public function resetCache(): void {
    $this->settings = [];
  }

}
