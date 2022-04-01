<?php

namespace Drupal\taxonomy_access_fix;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\VocabularyAccessControlHandler as OriginalVocabularyAccessControlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends access control for Taxonomy Vocabulary entities.
 */
class VocabularyAccessControlHandler extends OriginalVocabularyAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new VocabularyAccessControlHandler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation !== 'reorder_terms') {
      $access_result = parent::checkAccess($entity, $operation, $account);
      if (in_array($operation, ['access taxonomy overview', 'view'])) {
        $taxonomy_term_access_control_handler = $this->entityTypeManager->getAccessControlHandler('taxonomy_term');
        $access_result_operation = AccessResult::allowedIf($taxonomy_term_access_control_handler->createAccess($entity->id(), $account))
          ->orIf(AccessResult::allowedIf($account->hasPermission('delete terms in ' . $entity->id())))
          ->orIf(AccessResult::allowedIf($account->hasPermission('edit terms in ' . $entity->id())))
          ->orIf($this->checkAccess($entity, 'reorder_terms', $account));
        /** @var \Drupal\Core\Access\AccessResult $access_result */
        $access_result = $access_result
          ->andIf($access_result_operation);
        $access_result->cachePerPermissions()
          ->addCacheableDependency($entity);
        if (!$access_result->isAllowed()) {
          /** @var \Drupal\Core\Access\AccessResultReasonInterface $access_result */
          $access_result->setReason("The 'access taxonomy overview' and one of the 'create terms in {$entity->id()}', 'delete terms in {$entity->id()}', 'edit terms in {$entity->id()}', 'reorder terms in {$entity->id()}' permissions OR the 'administer taxonomy' permission are required.");
        }
      }
      return $access_result;
    }
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    $access_result = AccessResult::forbidden();
    if ($operation === 'reorder_terms') {
      $access_result = AccessResult::allowedIfHasPermission($account, "reorder terms in {$entity->id()}")
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
      if (!$access_result->isAllowed()) {
        /** @var \Drupal\Core\Access\AccessResultReasonInterface $access_result */
        $access_result->setReason("The 'reorder terms in {$entity->id()}' OR the 'administer taxonomy' permission is required.");
      }
    }
    return $access_result;
  }

}
