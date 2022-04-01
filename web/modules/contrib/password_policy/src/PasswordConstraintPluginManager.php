<?php

namespace Drupal\password_policy;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager that controls password constraints.
 */
class PasswordConstraintPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new PasswordConstraintPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PasswordConstraint', $namespaces, $module_handler, 'Drupal\password_policy\PasswordConstraintInterface', 'Drupal\password_policy\Annotation\PasswordConstraint');
    $this->alterInfo('password_policy_constraint_info');
    $this->setCacheBackend($cache_backend, 'password_policy_constraint');
  }

}
