<?php

namespace Drupal\nys_openleg_api\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Openleg API Request Manager service.
 */
class RequestManager extends DefaultPluginManager {

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
          'Plugin/OpenlegApi/Request',
          $namespaces,
          $module_handler,
          'Drupal\nys_openleg_api\RequestPluginInterface',
          'Drupal\nys_openleg_api\Annotation\OpenlegApiRequest'
      );
    $this->setCacheBackend($cache_backend, 'openleg_api.requests');
    $this->setLogger($logger);
  }

  /**
   * Setter for logger property.
   */
  public function setLogger(LoggerChannel $logger): void {
    $this->logger = $logger;
  }

}
