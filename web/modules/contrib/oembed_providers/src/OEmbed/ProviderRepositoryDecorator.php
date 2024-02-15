<?php

namespace Drupal\oembed_providers\OEmbed;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\oembed_providers\Entity\OembedProvider;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;

/**
 * Decorates the oEmbed ProviderRepository provided by core Media module.
 */
final class ProviderRepositoryDecorator implements ProviderRepositoryInterface {

  /**
   * Retrieves and caches information about oEmbed providers.
   *
   * @var \Drupal\media\OEmbed\ProviderRepositoryInterface
   */
  protected $decorated;

  /**
   * OEmbed provider storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $oembedProviderStorage;

  /**
   * How long the provider data should be cached, in seconds.
   *
   * @var int
   */
  protected $maxAge;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * URL of a JSON document which contains a database of oEmbed providers.
   *
   * @var string
   */
  protected $providersUrl;

  /**
   * Whether or not the external providers list should be fetched.
   *
   * @var bool
   */
  protected $externalFetch;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \Drupal\media\OEmbed\ProviderRepositoryInterface $decorated
   *   Retrieves and caches information about oEmbed providers.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Manages entity type plugin definitions.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value store factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param int $max_age
   *   (optional) How long the cache data should be kept. Defaults to a week.
   */
  public function __construct(ProviderRepositoryInterface $decorated, EntityTypeManagerInterface $entity_type_manager, ClientInterface $http_client, ConfigFactoryInterface $config_factory, TimeInterface $time, KeyValueFactoryInterface $key_value_factory = NULL, LoggerChannelFactoryInterface $logger_factory = NULL, ModuleHandlerInterface $module_handler, $max_age = 604800) {
    $this->decorated = $decorated;
    $this->oembedProviderStorage = $entity_type_manager->getStorage('oembed_provider');
    $this->httpClient = $http_client;
    $this->providersUrl = $config_factory->get('media.settings')->get('oembed_providers_url');
    $this->externalFetch = $config_factory->get('oembed_providers.settings')->get('external_fetch');
    $this->time = $time;
    $this->keyValue = $key_value_factory->get('media');
    $this->logger = $logger_factory->get('media');
    $this->moduleHandler = $module_handler;
    $this->maxAge = (int) $max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $current_time = $this->time->getCurrentTime();
    $stored = $this->keyValue->get('oembed_providers');
    // If we have stored data that hasn't yet expired, return that. We need to
    // store the data in a key-value store because, if the remote provider
    // database is unavailable, we'd rather return stale data than throw an
    // exception. This means we cannot use a normal cache backend or expirable
    // key-value store, since those could delete the stale data at any time.
    if ($stored && $stored['expires'] > $current_time) {
      return $stored['data'];
    }

    $custom_providers = $this->getCustomProviders();

    if ($this->externalFetch) {
      try {
        $response = $this->httpClient->request('GET', $this->providersUrl);
      }
      catch (TransferException $e) {
        if (isset($stored['data'])) {
          // Use the stale data to fall back gracefully, but warn site
          // administrators that we used stale data.
          $this->logger->warning('Remote oEmbed providers could not be retrieved due to error: @error. Using previously stored data. This may contain out of date information.', [
            '@error' => $e->getMessage(),
          ]);
          return $stored['data'];
        }
        // We have no previous data and the request failed.
        throw new ProviderException("Could not retrieve the oEmbed provider database from $this->providersUrl", NULL, $e);
      }

      $providers = Json::decode((string) $response->getBody());

      if (!is_array($providers) || empty($providers)) {
        if (isset($stored['data'])) {
          // Use the stale data to fall back gracefully, but as above, warn site
          // administrators that we used stale data.
          $this->logger->warning('Remote oEmbed providers database returned invalid or empty list. Using previously stored data. This may contain out of date information.');
          return $stored['data'];
        }
        // We have no previous data and the current data is corrupt.
        throw new ProviderException('Remote oEmbed providers database returned invalid or empty list.');
      }

      // Providers defined by provider database cannot be modified by
      // custom oEmbed provider definitions.
      $providers = array_merge($custom_providers, $providers);
    }
    else {
      $providers = $custom_providers;
    }

    usort($providers, function ($a, $b) {
      return strcasecmp($a['provider_name'], $b['provider_name']);
    });

    $this->moduleHandler->alter('oembed_providers', $providers);

    $keyed_providers = [];
    foreach ($providers as $provider) {
      try {
        $name = (string) $provider['provider_name'];
        $keyed_providers[$name] = new Provider($provider['provider_name'], $provider['provider_url'], $provider['endpoints']);
      }
      catch (ProviderException $e) {
        // Skip invalid providers, but log the exception message to help with
        // debugging.
        $this->logger->warning($e->getMessage());
      }
    }
    $this->keyValue->set('oembed_providers', [
      'data' => $keyed_providers,
      'expires' => $current_time + $this->maxAge,
    ]);

    return $keyed_providers;
  }

  /**
   * {@inheritdoc}
   */
  public function get($provider_name) {
    $providers = $this->getAll();

    if (!isset($providers[$provider_name])) {
      throw new \InvalidArgumentException("Unknown provider '$provider_name'");
    }
    return $providers[$provider_name];
  }

  /**
   * Returns custom providers in format identical to decoded providers.json.
   *
   * @return array
   *   Custom providers.
   */
  public function getCustomProviders() {
    return array_map(function (OembedProvider $custom_provider) {
      $endpoints = array_map(function (array $endpointData) {
        $endpoint = [
          'schemes' => $endpointData['schemes'],
          'url' => $endpointData['url'],
          'formats' => array_keys(array_filter($endpointData['formats'])),
        ];

        if ($endpointData['discovery']) {
          $endpoint['discovery'] = $endpointData['discovery'];
        }

        return $endpoint;
      }, $custom_provider->get('endpoints'));

      return [
        'provider_name' => $custom_provider->get('label'),
        'provider_url' => $custom_provider->get('provider_url'),
        'endpoints' => $endpoints,
      ];
    }, $this->oembedProviderStorage->loadMultiple());
  }

}
