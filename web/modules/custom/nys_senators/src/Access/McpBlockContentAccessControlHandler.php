<?php

namespace Drupal\nys_senators\Access;

use Drupal\block_content\BlockContentAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\nys_users\UsersHelper;

/**
 * Custom content block access control handler for MCPs.
 */
class McpBlockContentAccessControlHandler extends BlockContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $current_user = UsersHelper::resolveUser();
    if (UsersHelper::isMcp($current_user)) {
      return AccessResult::allowed();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $current_user = UsersHelper::resolveUser();
    if (UsersHelper::isMcp($current_user)) {
      return AccessResult::allowed();
    }

    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
