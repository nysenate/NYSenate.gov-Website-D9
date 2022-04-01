<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\user\Entity\Role;

/**
 * Checks whether untrusted roles have restricted permissions.
 */
class AdminPermissions extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Drupal permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'admin_permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Get every permission.
    $all_permissions = $this->security()->permissions(TRUE);
    $all_permission_strings = array_keys($all_permissions);

    // Get permissions for untrusted roles.
    $untrusted_permissions = $this->security()->untrustedPermissions(TRUE);
    foreach ($untrusted_permissions as $rid => $permissions) {
      $intersect = array_intersect($all_permission_strings, $permissions);
      foreach ($intersect as $permission) {
        if (isset($all_permissions[$permission]['restrict access'])) {
          $findings[$rid][] = $permission;
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("Drupal's permission system is extensive and allows for varying degrees of control. Certain permissions would allow a user total control, or the ability to escalate their control, over your site and should only be granted to trusted users.");
    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Admin and trusted Drupal permissions'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $output = [];

    foreach ($result->findings() as $rid => $permissions) {
      $role = Role::load($rid);
      /** @var Role $role */
      $paragraphs = [];
      $paragraphs[] = $this->t(
        "@role has the following restricted permissions:",
        [
          '@role' => Link::createFromRoute(
            $role->label(),
            'entity.user_role.edit_permissions_form',
            ['user_role' => $role->id()]
          )->toString(),
        ]
      );

      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $permissions,
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $output = '';

    foreach ($result->findings() as $rid => $permissions) {
      $role = Role::load($rid);
      /** @var Role $role */

      $output .= $this->t(
        '@role has @permissions',
        [
          '@role' => $role->label(),
          '@permissions' => implode(', ', $permissions),
        ]
      );
      $output .= "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Untrusted roles do not have administrative or trusted Drupal permissions.');

      case CheckResult::FAIL:
        return $this->t('Untrusted roles have been granted administrative or trusted Drupal permissions.');

      default:
        return $this->t("Unexpected result.");
    }
  }

}
