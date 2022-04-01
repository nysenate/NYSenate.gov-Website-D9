<?php

namespace Drupal\fancy_file_delete\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Unmanaged Files entity.
 *
 * @see \Drupal\fancy_file_delete\Entity\UnmanagedFiles.
 */
class UnmanagedFilesAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\fancy_file_delete\Entity\UnmanagedFilesInterface $entity */

    switch ($operation) {

      case 'view':


        return AccessResult::allowedIfHasPermission($account, 'view unmanaged files entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit unmanaged files entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete unmanaged files entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add unmanaged files entities');
  }

}
