<?php

namespace Drupal\contact_permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the permissions for accessing per role contact forms.
 */
class ContactPermissionsPermissions {

  use StringTranslationTrait;

  /**
   * Get access per role contact forms permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    // Generate permissions for each user role.
    $permissions = [];
    /* @var \Drupal\user\RoleInterface[] $roles */
    $roles = user_roles(TRUE);
    if (count($roles) < 1) {
      return $permissions;
    }

    foreach ($roles as $role) {
      $role_name = $role->label();
      $role_id = $role->id();
      $permissions["use $role_id personal contact forms"] = [
        'title' => $this->t("Use %role_name's personal contact forms", ['%role_name' => $role_name]),
      ];
    }

    return $permissions;
  }

}
