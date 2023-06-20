<?php

namespace Drupal\nys_openleg\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Openleg API Request Manager service.
 */
class ApiRequestManager extends DefaultPluginManager {

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Openleg Response Manager service.
   *
   * @var \Drupal\nys_openleg\Service\ApiResponseManager
   */
  protected $responseManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerChannel $logger, ApiResponseManager $responseManager) {
    parent::__construct(
          'Plugin/OpenlegApi/Request',
          $namespaces,
          $module_handler,
          'Drupal\nys_openleg\Api\RequestPluginInterface',
          'Drupal\nys_openleg\Annotation\OpenlegApiRequest'
      );
    $this->setCacheBackend($cache_backend, 'openleg_api.requests');
    $this->logger = $logger;
    $this->responseManager = $responseManager;
  }

}
