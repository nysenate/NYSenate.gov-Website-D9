<?php

namespace Drupal\webform_node_analysis;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Adds devel links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    $entity_types['node']->setFormClass('webform_analysis', 'Drupal\webform_analysis\Form\WebformAnalysisForm');
    $entity_types['node']->setLinkTemplate('webform.results_analysis', '/node/{node}/webform/results/analysis/{field_name}');
  }

  /**
   * Adds devel operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity->getEntityTypeId() != 'node') {
      return $operations;
    }

    static $webform_field_names;
    if (!$webform_field_names) {
      $webform_field_names = [];

      /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
      $field_configs = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['entity_type' => 'node']);
      foreach ($field_configs as $field_config) {
        if ($field_config->get('field_type') === 'webform') {
          $bundle = $field_config->get('bundle');
          $field_name = $field_config->get('field_name');
          $webform_field_names[$bundle][$field_name] = $field_config->getLabel();
        }
      }
    }

    $operations = [];
    if ($this->currentUser->hasPermission('view any webform submission')) {
      $bundle = $entity->bundle();
      if (isset($webform_field_names[$bundle]) && $entity->hasLinkTemplate('webform.results_analysis')) {
        $weight = 100;
        foreach ($webform_field_names[$bundle] as $field_name => $field_label) {
          $operations['analysis'] = [
            'title'  => $this->t('Analysis @field', ['@field' => $field_label]),
            'weight' => $weight++,
            'url'    => $entity->toUrl('webform.results_analysis')->setRouteParameter('field_name', $field_name),
          ];
        }
      }
    }
    return $operations;
  }

}
