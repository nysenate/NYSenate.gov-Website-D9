<?php

namespace Drupal\password_policy;

/**
 * Interface PasswordPolicyValidationManagerInterface.
 *
 * Decides whether validation is required and whether to display the policy
 * table.
 *
 * @package Drupal\password_policy
 */
interface PasswordPolicyValidationManagerInterface {

  /**
   * Check if the password policy validation table should be shown.
   *
   * @return bool
   *   True if the policy table should be shown, else FALSE.
   */
  public function tableShouldBeVisible();

  /**
   * Check if the password policy validation should run for the current user.
   *
   * @return bool
   *   True if the validation should run, else FALSE.
   */
  public function validationShouldRun();

}
