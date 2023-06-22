<?php

namespace Drupal\contact_permissions\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\contact\Access\ContactPageAccess;

/**
 * Access check for contact_personal_page route.
 */
class ContactPermissionsContactPageAccess extends ContactPageAccess {

  /**
   * {@inheritdoc}
   */
  public function access(UserInterface $user, AccountInterface $account) {
    /* \Drupal\Core\Access\AccessResult $access */
    $access = parent::access($user, $account);

    if (!$access->isAllowed() && $access->getReason() == "The 'access user contact forms' permission is required.") {
      foreach ($user->getRoles() as $role_id) {
        $permission_access = AccessResult::allowedIfHasPermission($account, "use $role_id personal contact forms");
        if ($permission_access->isAllowed()) {
          $access = $permission_access;
          break;
        }
      }
    }

    return $access->andif(AccessResult::allowedIfHasPermission($user, 'have a personal contact form'));
  }

}
