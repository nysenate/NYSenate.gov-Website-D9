<?php

namespace Drupal\nys_senators\Service;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_senators\ManagementPageInterface;

/**
 * A wrapper around QueueDatabaseFactory.
 *
 * Subscription queues are registered services.  This manager acts as a service
 * collector for all registered queues.
 */
class DashboardPageManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * The inventory of page plugins.
   *
   * @var array
   */
  protected array $pages = [];

  /**
   * The id of the default plugin.
   *
   * @var string
   */
  protected string $defaultId = 'overview';

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
          'Plugin/NysDashboard',
          $namespaces,
          $module_handler,
          'Drupal\nys_senators\ManagementPageInterface',
          'Drupal\nys_senators\Annotation\SenatorManagementPage'
      );
    $this->setCacheBackend($cache_backend, 'nys_senators.dashboard.pages');
  }

  /**
   * Gets a page by plugin name.
   *
   * @todo Create "default page" mechanism.
   */
  public function getPage(string $plugin_id, bool $or_default = TRUE): ?ManagementPageInterface {
    try {
      $ret = $this->createInstance($plugin_id);
    }
    catch (\Throwable $e) {
      $ret = NULL;
    }
    return $ret;
  }

  /**
   * Getter for DefaultId.
   */
  public function getDefaultId(): string {
    return $this->defaultId;
  }

  /**
   * Setter for DefaultId.
   */
  public function setDefaultId(string $defaultId): void {
    $this->defaultId = $defaultId;
  }

  /**
   * {@inheritDoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return $this->getDefaultId();
  }

}
