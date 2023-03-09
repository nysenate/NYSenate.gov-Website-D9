<?php

namespace Drupal\nys_senators\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class for adding custom MCP access checking.
 *
 * @package Drupal\nys_senators\Access
 *
 * Removes MCP access to admin block pages to mitigate administer blocks perm.
 */
class McpAccessCheck implements AccessInterface {

  /**
   * Checks access to the block add page for the block type.
   */
  public function access(AccountInterface $account) {

    // What administrative roles need access.
    $admin_roles = [
      'content_admin',
      'administrator',
    ];

    $roles = $account->getRoles();
    // Check if MCP but not with an admin role.
    if (in_array('microsite_content_producer', $roles) && empty(array_intersect($admin_roles, $roles))) {
      // Deny access to any block admin pages.
      return AccessResultForbidden::forbidden()->setCacheMaxAge(0);
    }

    // Default back to permissions otherwise.
    return AccessResult::allowedIfHasPermission($account, "administer blocks");
  }

}
