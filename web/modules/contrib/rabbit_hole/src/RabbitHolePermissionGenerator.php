<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Generates permission for each supported entity type.
 */
class RabbitHolePermissionGenerator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity helper.
   *
   * @var \Drupal\rabbit_hole\EntityHelper
   */
  protected EntityHelper $entityHelper;

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManager
   */
  protected $rhBehaviorSettingsManager;

  /**
   * Constructor.
   */
  public function __construct(EntityHelper $entity_helper, BehaviorSettingsManagerInterface $behavior_settings_manager) {
    $this->entityHelper = $entity_helper;
    $this->rhBehaviorSettingsManager = $behavior_settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rabbit_hole.entity_helper'),
      $container->get('rabbit_hole.behavior_settings_manager'),
    );
  }

  /**
   * Return an array of per-entity Rabbit Hole permissions.
   *
   * @return array
   *   An array of permissions.
   */
  public function permissions(): array {
    $permissions = [];

    foreach ($this->entityHelper->getSupportedEntityTypes() as $entity_type) {
      if ($this->rhBehaviorSettingsManager->entityTypeIsEnabled($entity_type->id())) {
        $permissions += $this->buildPermissions($entity_type);
      }
    }
    return $permissions;
  }

  /**
   * Returns a list of Rabbit Hole permissions for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EntityTypeInterface $entity_type): array {
    $type_id = $entity_type->id();
    $type_params = ['%entity_type' => $entity_type->getLabel()];
    $dependencies = [
      'module' => [$entity_type->getProvider()],
    ];

    return [
      "rabbit hole administer $type_id" => [
        'title' => $this->t('Administer Rabbit Hole settings for %entity_type', $type_params),
        'dependencies' => $dependencies,
      ],
      "rabbit hole bypass $type_id" => [
        'title' => $this->t('Bypass Rabbit Hole action for %entity_type', $type_params),
        'dependencies' => $dependencies,
      ],
    ];
  }

}
