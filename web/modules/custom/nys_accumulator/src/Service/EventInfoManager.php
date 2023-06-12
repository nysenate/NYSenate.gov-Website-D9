<?php

namespace Drupal\nys_accumulator\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Management class for the accumulator's event info generators.
 */
class EventInfoManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/EventInfoGenerator',
      $namespaces,
      $module_handler,
      'Drupal\nys_accumulator\EventInfoGeneratorInterface',
      'Drupal\nys_accumulator\Annotation\EventInfoGenerator'
    );
    $this->setCacheBackend($cache_backend, 'nys_accumulator.info_generators');
  }

}
