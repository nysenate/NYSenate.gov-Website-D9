<?php

namespace Drupal\media_migration;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\media_migration\Annotation\FileDealer;

/**
 * Manages discovery and instantiation of plain file dealer plugins.
 *
 * @see \Drupal\media_migration\FieldTypeDealerManagerInterface
 */
class FileDealerManager extends DefaultPluginManager implements FileDealerManagerInterface {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new FileDealerManager.
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
      'Plugin/media_migration/file',
      $namespaces,
      $module_handler,
      FileDealerPluginInterface::class,
      FileDealer::class
    );

    $this->alterInfo('media_migration_file_dealer_info');
    $this->setCacheBackend($cache_backend, 'file_dealer_plugins');
  }

  /**
   * Gets the plugin definitions for the specified scheme and main MIME type.
   *
   * @param string $scheme
   *   The URI scheme.
   * @param string $mime
   *   The main MIME type (the mime type's first part, before the slash).
   *
   * @return mixed[]
   *   An array of the matching plugin definitions (empty array if no
   *   definitions were found).
   */
  protected function getDefinitionsByFieldTypeAndScheme(string $scheme, string $mime) {
    $definitions = $this->getDefinitions();

    $strict_list = array_filter($definitions, function ($definition) use ($scheme, $mime) {
      return in_array($scheme, $definition['schemes'], TRUE) && in_array($mime, $definition['mimes'], TRUE);
    });
    if (!empty($strict_list)) {
      return $strict_list;
    }

    $only_mime_list = array_filter($definitions, function ($definition) use ($mime) {
      return empty($definition['schemes']) && in_array($mime, $definition['mimes'], TRUE);
    });
    if (!empty($only_mime_list)) {
      return $only_mime_list;
    }

    $only_scheme_list = array_filter($definitions, function ($definition) use ($scheme) {
      return in_array($scheme, $definition['schemes'], TRUE) && empty($definition['mimes']);
    });
    if (!empty($only_scheme_list)) {
      return $only_scheme_list;
    }

    if (array_key_exists('fallback', $definitions)) {
      return ['fallback' => $definitions['fallback']];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function createInstanceFromSchemeAndMime(string $scheme, string $mime) {
    $filtered_definitions = $this->getDefinitionsByFieldTypeAndScheme($scheme, $mime);
    if (!empty($filtered_definitions)) {
      try {
        $configuration = [
          'scheme' => $scheme,
          'mime' => $mime,
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
