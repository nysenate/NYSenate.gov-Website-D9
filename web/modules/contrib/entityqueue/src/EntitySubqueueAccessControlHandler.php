<?php

namespace Drupal\entityqueue;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entityqueue\Entity\EntityQueue;

/**
 * Defines the access control handler for the entity_subqueue entity type.
 *
 * @see \Drupal\entityqueue\Entity\EntitySubqueue
 */
class EntitySubqueueAccessControlHandler extends EntityAccessControlHandler {

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
        return AccessResult::allowedIfHasPermissions($account, ["update {$entity->bundle()} entityqueue", 'manipulate all entityqueues', 'administer entityqueue'], 'OR');
      break;

      case 'delete':
        $can_delete_subqueue = AccessResult::allowedIf(!$entity->getQueue()->getHandlerPlugin()->hasAutomatedSubqueues());

        $access_result = AccessResult::allowedIfHasPermissions($account, ["delete {$entity->bundle()} entityqueue", 'manipulate all entityqueues', 'administer entityqueue'], 'OR')
          ->andIf($can_delete_subqueue)
          ->addCacheableDependency($entity->getQueue());

        return $access_result;
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
    $access_result = AccessResult::allowedIfHasPermissions($account, ["create {$entity_bundle} entityqueue", 'manipulate all entityqueues', 'administer entityqueue'], 'OR');

    if ($entity_bundle) {
      $queue = EntityQueue::load($entity_bundle);
      $access_result = AccessResult::allowedIf(!$queue->getHandlerPlugin()->hasAutomatedSubqueues());
      $access_result->addCacheableDependency($queue);
    }

    return $access_result;
  }

}
