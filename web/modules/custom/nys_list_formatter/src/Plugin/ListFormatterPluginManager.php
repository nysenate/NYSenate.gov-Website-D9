<?php

namespace Drupal\nys_list_formatter\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for all views plugins.
 */
class ListFormatterPluginManager extends DefaultPluginManager {

  /**
   * Constructs the FieldTypePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/list_formatter', $namespaces, $module_handler, ListFormatterListInterface::class, 'Drupal\nys_list_formatter\Annotation\ListFormatter');
    $this->alterInfo('field_info');
    $this->setCacheBackend($cache_backend, 'list_formatter_plugins');
  }

  /**
   * Returns an array of info to add to hook_field_formatter_info_alter().
   *
   * This iterates through each item returned from fieldListInfo.
   *
   * @param bool $module_key
   *   Field value.
   *
   * @return array
   *   An array of fields and settings from hook_list_formatter_field_info data
   *   implementations. Containing an aggregated array from all items.
   */
  public function fieldListInfo($module_key = FALSE) {
    $field_info = [
      'field_types' => [],
      'settings' => [],
    ];
    // Create array of all field types and default settings.
    foreach ($this->getDefinitions() as $id => $definition) {
      if (isset($definition['field_types'])) {
        $field_types = [];
        if ($module_key) {
          // @todo Add the module and key by plugin id, so they can be independent.
          $module = $definition['module'];
          // Add field types by module.
          foreach ($definition['field_types'] as $type) {
            $field_types[$module][] = $type;
          }
        }
        else {
          $field_types = $definition['field_types'];
        }
        if ($field_types) {
          $field_info['field_types'] = NestedArray::mergeDeep($field_info['field_types'], $field_types);
        }
      }
      if (!empty($definition['settings'])) {
        $field_info['settings'] = NestedArray::mergeDeep($field_info['settings'], $definition['settings']);
      }
    }
    return $field_info;
  }

}
