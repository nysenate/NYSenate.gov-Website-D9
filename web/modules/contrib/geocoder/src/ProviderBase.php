<?php

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for providers using handlers.
 */
abstract class ProviderBase extends PluginBase implements ProviderInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend used to cache geocoding data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The configurable language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a geocoder provider plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend used to cache geocoding data.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The Drupal language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cache_backend;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('cache.geocoder'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($source) {
    return $this->process(__FUNCTION__, \func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function reverse($latitude, $longitude) {
    return $this->process(__FUNCTION__, \func_get_args());
  }

  /**
   * Provides a helper callback for geocode() and reverse().
   *
   * @param string $method
   *   The method: 'geocode' or 'reverse'.
   * @param array $data
   *   An array with data to be processed. When geocoding, it contains only one
   *   item with the string. When reversing, contains 2 items: the latitude and
   *   the longitude.
   *
   * @return \Geocoder\Model\Address|null
   *   The Address, NULL otherwise.
   */
  protected function process($method, array $data) {
    if ($caching = $this->configFactory->get('geocoder.settings')->get('cache')) {
      // Try to retrieve from cache first.
      $cid = $this->getCacheId($method, $data);
      if ($cache = $this->cacheBackend->get($cid)) {
        return $cache->data;
      }
    }

    // Call the processor.
    $processor = $method == 'geocode' ? 'doGeocode' : 'doReverse';
    $value = \call_user_func_array([$this, $processor], $data);

    if ($caching) {
      // Cache the result.
      $this->cacheBackend->set($cid, $value);
    }

    return $value;
  }

  /**
   * Performs the geocoding.
   *
   * @param string $source
   *   The data to be geocoded.
   *
   * @return \Geocoder\Model\AddressCollection|\Geometry|null
   *   The address collection, or the geometry, or NULL.
   */
  abstract protected function doGeocode($source);

  /**
   * Performs the reverse geocode.
   *
   * @param float $latitude
   *   The latitude.
   * @param float $longitude
   *   The longitude.
   *
   * @return \Geocoder\Model\AddressCollection|null
   *   The AddressCollection, NULL otherwise.
   */
  abstract protected function doReverse($latitude, $longitude);

  /**
   * Builds a cached id.
   *
   * @param string $method
   *   The method: 'geocode' or 'reverse'.
   * @param array $data
   *   An array with data to be processed. When geocoding, it contains only one
   *   item with the string. When reversing, contains 2 items: the latitude and
   *   the longitude.
   *
   * @return string
   *   An unique cache id.
   */
  protected function getCacheId($method, array $data): string {
    $cid = [$method, $this->getPluginId()];
    $cid[] = sha1(serialize($this->configuration) . serialize($data));

    return implode(':', $cid);
  }

}
