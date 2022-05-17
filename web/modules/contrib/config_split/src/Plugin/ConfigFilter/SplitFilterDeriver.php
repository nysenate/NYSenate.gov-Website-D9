<?php

namespace Drupal\config_split\Plugin\ConfigFilter;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\config_split\Plugin\ConfigFilter\SplitFilter
 */
class SplitFilterDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The config Factory to load the overridden configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SplitFilter constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage to load the split entities from.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to load the configuration including overrides from.
   */
  public function __construct(EntityStorageInterface $entity_storage, ConfigFactoryInterface $config_factory) {
    $this->entityStorage = $entity_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('config_split'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityStorage->loadMultiple() as $name => $entity) {
      $config_name = $entity->getConfigDependencyName();
      $config = $this->configFactory->get($config_name);
      $this->derivatives[$name] = $base_plugin_definition;
      $this->derivatives[$name]['label'] = $entity->label();
      $this->derivatives[$name]['config_name'] = $config_name;
      // The weight and status can be overwritten in settings.php, however,
      // the cache has to ble cleared for changes in overrides to take effect.
      $this->derivatives[$name]['weight'] = $config->get('weight');
      $this->derivatives[$name]['status'] = $config->get('status');
      $this->derivatives[$name]['config_dependencies']['config'] = [$config_name];
    }
    return $this->derivatives;
  }

}
