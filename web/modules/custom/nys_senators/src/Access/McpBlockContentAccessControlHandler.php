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
    // If user is an MCP, grant access to content blocks tied to their senator.
    $current_user = UsersHelper::resolveUser();
    if (UsersHelper::isMcp($current_user)) {
      $managed_senator_tids = UsersHelper::getManagedSenators($current_user);
      $is_block_linked_to_managed_senator = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('field_block', $entity->id(), 'CONTAINS')
        ->condition('field_senator_multiref', $managed_senator_tids, 'IN')
        ->count()
        ->execute();
      if ($is_block_linked_to_managed_senator) {
        return AccessResult::allowed();
      }
    }

    // Otherwise, fall back on core access rules.
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // If user is an MCP, grant access to creating new content blocks.
    $current_user = UsersHelper::resolveUser();
    if (UsersHelper::isMcp($current_user)) {
      return AccessResult::allowed();
    }

    // Otherwise, fall back on core access rules.
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
