<?php

namespace Drupal\nys_openleg_api\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_openleg_api\RequestPluginInterface;

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
   * Local cache for requesters, to enforce singletons.  Keyed by plugin ID.
   *
   * @var \Drupal\nys_openleg_api\RequestPluginInterface[]
   */
  protected array $allRequesters = [];

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

  /**
   * Instantiates a requester.  Uses local cache to enforce singleton per type.
   *
   * @param string $item_type
   *   The plugin name.
   *
   * @return \Drupal\nys_openleg_api\RequestPluginInterface|null
   *   The instantiated requester, or NULL on error.
   */
  public function resolve(string $item_type): ?RequestPluginInterface {
    if (!($this->allRequesters[$item_type] ?? NULL)) {
      try {
        $ret = $this->createInstance($item_type);
      }
      catch (\Throwable $e) {
        $this->logger->error(
          'Could not instantiate request plugin "@name"',
          ['@name' => $item_type, '@msg' => $e->getMessage()]
        );
        $ret = NULL;
      }
      $this->allRequesters[$item_type] = $ret;
    }
    return $this->allRequesters[$item_type];
  }

}
