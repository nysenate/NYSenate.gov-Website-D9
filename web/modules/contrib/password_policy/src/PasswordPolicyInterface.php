<?php

namespace Drupal\password_policy;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a password policy entity.
 */
interface PasswordPolicyInterface extends ConfigEntityInterface {

  /**
   * Return the constraints from the policy.
   *
   * @return array
   *   All constraint configurations for the policy.
   */
  public function getConstraints();

  /**
   * Return a specific constraint from the policy.
   *
   * @param string $key
   *   The constraint ID from the individual policy configuration.
   *
   * @return array
   *   A specific constraint configuration in the policy.
   */
  public function getConstraint($key);

  /**
   * Return the password reset setting from the policy.
   *
   * @return int
   *   The number of days between password resets.
   */
  public function getPasswordReset();

  /**
   * Return the user roles for the policy.
   *
   * @return array
   *   The user roles assigned to the policy.
   */
  public function getRoles();

  /**
   * Return whether to display the password policy table on validation.
   *
   * @return bool
   *   TRUE if the policy table should be shown, FALSE otherwise.
   */
  public function isPolicyTableShown();

}
