<?php

namespace Drupal\media_migration;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The manager of Media WYSIWYG plugins.
 *
 * Manages Media Wysiwyg plugins which add Media Migration's migrate process
 * plugins to the value process pipeline of formatted text fields.
 *
 * The discovered plugins can be altered by implementing the
 * hook_media_wysiwyg_info_alter() hook.
 *
 * @see \Drupal\media_migration\Annotation\MediaWysiwyg
 * @see \Drupal\media_migration\MediaWysiwygInterface
 * @see \Drupal\media_migration\MigratePluginAlterer::addMediaWysiwygProcessor
 */
class MediaWysiwygPluginManager extends DefaultPluginManager {

  /**
   * Constructs MediaWysiwygPluginManager object.
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
    parent::__construct(
      'Plugin/MediaWysiwyg',
      $namespaces,
      $module_handler,
      'Drupal\media_migration\MediaWysiwygInterface',
      'Drupal\media_migration\Annotation\MediaWysiwyg'
    );
    $this->alterInfo('media_wysiwyg_info');
    $this->setCacheBackend($cache_backend, 'media_wysiwyg_plugins');
  }

  /**
   * Returns a media wysiwyg plugin instance for the given source entity type.
   *
   * @param string $source_entity_type_id
   *   The entity type ID on the source.
   *
   * @return \Drupal\media_migration\MediaWysiwygInterface
   *   A media wysiwyg plugin instance for the given source entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createInstanceFromSourceEntityType(string $source_entity_type_id) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (in_array($source_entity_type_id, array_keys($plugin_definition['entity_type_map']), TRUE)) {
        return $this->createInstance(
          $plugin_id,
          ['source_entity_type_id' => $source_entity_type_id]
        );
      }
    }

    throw new PluginNotFoundException('', sprintf(
      "No MediaWysiwyg plugin was found for source entity type '%s'.",
      $source_entity_type_id
    ));
  }

}
