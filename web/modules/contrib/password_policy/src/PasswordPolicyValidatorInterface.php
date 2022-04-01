<?php

namespace Drupal\password_policy;

use Drupal\user\UserInterface;

/**
 * Interface Password Policy Validator Interface.
 *
 * @package Drupal\password_policy
 */
interface PasswordPolicyValidatorInterface {

  /**
   * Validates the given password.
   *
   * @param string $password
   *   The new password.
   * @param \Drupal\user\UserInterface $user
   *   The current user object.
   * @param array $edited_user_roles
   *   An optional array containing the edited user roles.
   *
   * @return \Drupal\password_policy\PasswordPolicyValidationReport
   *   Validation report object.
   */
  public function validatePassword(string $password, UserInterface $user, array $edited_user_roles = []): PasswordPolicyValidationReport;

  /**
   * Builds the password policy constraints table rows.
   *
   * @param string $password
   *   The new password.
   * @param \Drupal\user\UserInterface $user
   *   The current user object.
   * @param array $edited_user_roles
   *   An optional array containing the edited user roles.
   *
   * @return array
   *   An array containing the constraints table rows.
   */
  public function buildPasswordPolicyConstraintsTableRows(string $password, UserInterface $user, array $edited_user_roles = []): array;

}
