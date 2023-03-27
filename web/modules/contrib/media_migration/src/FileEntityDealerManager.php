<?php

namespace Drupal\media_migration;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\media_migration\Annotation\FileEntityDealer;

/**
 * Manages discovery and instantiation of file entity dealer plugins.
 *
 * @see \Drupal\media_migration\FileEntityDealerPluginInterface
 */
class FileEntityDealerManager extends DefaultPluginManager implements FileEntityDealerManagerInterface {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a FileEntityDealerManager instance.
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
      'Plugin/media_migration/file_entity',
      $namespaces,
      $module_handler,
      FileEntityDealerPluginInterface::class,
      FileEntityDealer::class
    );

    $this->alterInfo('media_migration_file_entity_dealer_info');
    $this->setCacheBackend($cache_backend, 'file_entity_dealer_plugins');
  }

  /**
   * Gets the plugin definitions for the specified file entity type.
   *
   * @param string $type
   *   The file entity type.
   * @param string $scheme
   *   The URI scheme.
   *
   * @return mixed[]
   *   An array of the matching plugin definitions (empty array if no
   *   definitions were found).
   */
  protected function getDefinitionsByTypeAndScheme(string $type, string $scheme) {
    $definitions = $this->getDefinitions();
    $list = [];

    $strict_list = array_filter($this->getDefinitions(), function ($definition) use ($type, $scheme) {
      return in_array($type, $definition['types'], TRUE) && in_array($scheme, $definition['schemes'], TRUE);
    });
    $list = array_merge(
      $list,
      $strict_list,
    );

    $only_scheme_list = array_filter($definitions, function ($definition) use ($scheme) {
      return empty($definition['types']) && in_array($scheme, $definition['schemes'], TRUE);
    });
    $list = array_merge(
      $list,
      $only_scheme_list,
    );

    $only_type_list = array_filter($definitions, function ($definition) use ($type) {
      return in_array($type, $definition['types'], TRUE) && empty($definition['schemes']);
    });
    $list = array_merge(
      $list,
      $only_type_list,
    );

    if (empty($list) && array_key_exists('fallback', $definitions)) {
      return ['fallback' => $definitions['fallback']];
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstanceFromTypeAndScheme(string $type, string $scheme) {
    $filtered_definitions = $this->getDefinitionsByTypeAndScheme($type, $scheme);
    if (!empty($filtered_definitions)) {
      try {
        $configuration = [
          'type' => $type,
          'scheme' => $scheme,
        ];
        $plugin_id = array_keys($filtered_definitions)[0];
        return $this->createInstance($plugin_id, $configuration);
      }
      catch (PluginException $e) {
        return NULL;
      }
    }
    return NULL;
  }

}
