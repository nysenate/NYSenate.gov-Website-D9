<?php

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Language\LanguageManagerInterface;
use Geocoder\StatefulGeocoder;
use Geocoder\Provider\Provider;
use Drupal\geocoder_geofield\Geocoder\Provider\GeometryProviderInterface;

/**
 * Provides a base class for providers using handlers.
 */
abstract class ProviderUsingHandlerBase extends ProviderBase {

  /**
   * The provider handler.
   *
   * @var \Geocoder\Provider\Provider
   */
  protected $handler;

  /**
   * The V4 Stateful handler wrapper.
   *
   * @var \Geocoder\StatefulGeocoder
   */
  protected $handlerWrapper;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager) {
    // The ProviderBase constructor needs to be run anyway (before possible
    // exception @throw), to allow the ProviderBase process method.
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $cache_backend, $language_manager);
    if (empty($plugin_definition['handler'])) {
      throw new InvalidPluginDefinitionException($plugin_id, "Plugin '$plugin_id' should define a handler.");
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   * @throws \Geocoder\Exception\Exception
   */
  protected function doGeocode($source) {
    // In case of a Geocoder Provider returning a \Geocoder\Collection.
    if ($this->getHandler() instanceof Provider) {
      return $this->getHandlerWrapper()->geocode($source);
    }
    // In case of a GeoPHP Geometry Provider returning a \Geometry.
    if ($this->getHandler() instanceof GeometryProviderInterface) {
      return $this->getHandler()->geocode($source);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   * @throws \Geocoder\Exception\Exception
   */
  protected function doReverse($latitude, $longitude) {
    return $this->getHandlerWrapper()->reverse($latitude, $longitude);
  }

  /**
   * Returns the provider handler.
   *
   * @return \Geocoder\Provider\Provider|\Drupal\geocoder_geofield\Geocoder\Provider\GeometryProviderInterface
   *   The provider plugin.
   *
   * @throws \ReflectionException
   */
  protected function getHandler() {
    if ($this->handler === NULL) {
      $definition = $this->getPluginDefinition();
      $reflection_class = new \ReflectionClass($definition['handler']);
      $this->handler = $reflection_class->newInstanceArgs($this->getArguments());
    }

    return $this->handler;
  }

  /**
   * Returns the V4 Stateful wrapper.
   *
   * @return \Geocoder\StatefulGeocoder
   *   The current handler wrapped in this class.
   *
   * @throws \ReflectionException
   */
  protected function getHandlerWrapper(): StatefulGeocoder {
    if ($this->handlerWrapper === NULL) {
      $this->handlerWrapper = new StatefulGeocoder(
        $this->getHandler(),
        $this->languageManager->getCurrentLanguage()->getId()
      );
    }

    return $this->handlerWrapper;
  }

  /**
   * Builds a list of arguments to be used by the handler.
   *
   * @return array
   *   The list of arguments for handler instantiation.
   */
  protected function getArguments(): array {
    $arguments = [];

    foreach ($this->getPluginDefinition()['arguments'] as $key => $argument) {
      // No default value has been passed.
      if (\is_string($key)) {
        $config_name = $key;
        $default_value = $argument;
      }
      else {
        $config_name = $argument;
        $default_value = NULL;
      }

      $arguments[] = $this->configuration[$config_name] ?? $default_value;
    }

    return $arguments;
  }

}
