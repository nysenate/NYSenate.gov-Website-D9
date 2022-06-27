<?php

namespace Drupal\entityqueue;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the entity_queue entity type.
 *
 * @see \Drupal\entityqueue\Entity\EntityQueue
 */
class EntityQueueAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');

      break;

      case 'update':
      case 'enable':
      case 'disable':
        return AccessResult::allowedIfHasPermissions($account, ["update {$entity->id()} entityqueue", 'manipulate all entityqueues', 'administer entityqueue'], 'OR');

      break;

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ["delete {$entity->id()} entityqueue", 'manipulate all entityqueues', 'administer entityqueue'], 'OR');

      break;

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer entityqueue');
  }

}
