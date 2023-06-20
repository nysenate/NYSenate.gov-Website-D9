<?php

namespace Drupal\nys_openleg\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Openleg API Response Manager service.
 */
class ApiResponseManager extends DefaultPluginManager {

  /**
   * Key for item-type requesters.
   */
  const OPENLEG_RESPONSE_TYPE_ITEM = 'item';

  /**
   * Key for search-type requesters.
   */
  const OPENLEG_RESPONSE_TYPE_SEARCH = 'search';

  /**
   * Key for update-type requesters.
   */
  const OPENLEG_RESPONSE_TYPE_UPDATE = 'update';

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

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

}
