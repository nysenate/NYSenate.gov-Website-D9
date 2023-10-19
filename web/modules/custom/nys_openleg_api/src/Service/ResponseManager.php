<?php

namespace Drupal\nys_openleg_api\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\Error;
use Drupal\nys_openleg_api\ResponsePluginInterface;

/**
 * Openleg API Response Manager service.
 */
class ResponseManager extends DefaultPluginManager {

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerChannel $logger) {
    parent::__construct(
          'Plugin/OpenlegApi/Response',
          $namespaces,
          $module_handler,
          'Drupal\nys_openleg\Api\ResponsePluginInterface',
          'Drupal\nys_openleg\Annotation\OpenlegApiResponse'
      );
    $this->setCacheBackend($cache_backend, 'openleg_api.responses');
    $this->logger = $logger;
  }

  /**
   * Setter for logger property.
   */
  public function setLogger(LoggerChannel $logger): void {
    $this->logger = $logger;
  }

  /**
   * Finds a type-specific response plugin which matches the API response.
   *
   * The exact type of response is indicated by the responseType property in
   * the response's result.  If a matching plugin cannot be found, an Error
   * response is used instead.
   *
   * @param object|null $response
   *   The full JSON-decoded response from Openleg (could be NULL).
   *
   * @return \Drupal\nys_openleg_api\ResponsePluginInterface
   *   An appropriate response object, or an Error Response object.
   */
  public function resolveResponse(?object $response): ResponsePluginInterface {

    // If responseType is not available, immediately fallback to "error".
    $type = $response->responseType ?? 'error';
    try {
      /** @var \Drupal\nys_openleg_api\ResponsePluginInterface $ret */
      $ret = $this->createInstance($type);
    }
    catch (\Throwable $e) {
      $this->logger->error(
        "Failed to instantiate response object",
        [
          '@type' => $type,
          '@message' => $e->getMessage(),
        ]
      );
      // Fallback to manual instantiation of an Error response.
      $ret = new Error();
    }
    $ret->init($response);

    return $ret;

  }

}
