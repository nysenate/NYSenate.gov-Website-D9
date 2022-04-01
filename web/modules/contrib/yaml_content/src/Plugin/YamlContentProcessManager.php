<?php

namespace Drupal\yaml_content\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\yaml_content\Annotation\YamlContentProcess;

/**
 * Manages discovery and instantiation of YAML Content process plugins.
 */
class YamlContentProcessManager extends DefaultPluginManager {

  /**
   * Constructs a YamlContentProcessManager object.
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
    parent::__construct("Plugin/yaml_content/process", $namespaces, $module_handler,
      YamlContentProcessInterface::class,
      YamlContentProcess::class);
    $this->setCacheBackend($cache_backend, 'yaml_content_process_plugins');
  }

  /**
   * Run any designated preprocessors on the provided field data.
   *
   * Preprocessors are expected to be provided in the following format:
   *
   * ```yaml
   *   '#process':
   *     callback: '<callback string>'
   *     args:
   *       - <callback argument 1>
   *       - <callback argument 2>
   *       - <...>
   * ```
   *
   * The callback function receives the following arguments:
   *
   *   - `$field`
   *   - `$field_data`
   *   - <callback argument 1>
   *   - <callback argument 2>
   *   - <...>
   *
   * The `$field_data` array is passed by reference and may be modified directly
   * by the callback implementation.
   *
   * @param \Drupal\yaml_content\Plugin\ProcessingContext $context
   *   The processing context.
   * @param array|string $field_data
   *   The field data.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function preprocessFieldData(ProcessingContext $context, &$field_data) {
    // Break here if the field data is not an array since there can be no
    // processing instructions included.
    if (!is_array($field_data)) {
      return;
    }

    // If there is no process element skip trying to process.
    if (!isset($field_data['#process'])) {
      return;
    }

    // If there is no process element skip trying to process.
    if (isset($field_data['#process'])) {
      $process_config = $field_data['#process'];
      if (isset($process_config['callback'])) {
        $plugin_id = $process_config['callback'];
        /** @var \Drupal\yaml_content\Plugin\YamlContentProcessInterface $plugin */
        $plugin = $this->createInstance($plugin_id, $process_config['args']);
        $plugin->process($context, $field_data);
      }
    }
  }

}
