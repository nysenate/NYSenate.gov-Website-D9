<?php

namespace Drupal\taxonomy_access_fix;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides additional permissions for entities provided by Taxonomy module.
 */
class TaxonomyAccessFixPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TaxonomyAccessFixPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Gets additional permissions for Taxonomy Vocabulary entities.
   *
   * @return array
   *   Permissions array.
   */
  public function getPermissions() {
    $permissions = [];

    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();

    foreach ($vocabularies as $vocabulary) {
      $permissions['view terms in ' . $vocabulary->id()] = [
        'title' => $this->t('View terms in %vocabulary', [
          '%vocabulary' => $vocabulary->label(),
        ]),
      ];
      $permissions['reorder terms in ' . $vocabulary->id()] = [
        'title' => $this->t('Reorder terms in %vocabulary', [
          '%vocabulary' => $vocabulary->label(),
        ]),
      ];
    }

    return $permissions;
  }

}
