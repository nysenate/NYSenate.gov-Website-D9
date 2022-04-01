<?php

namespace Drupal\password_policy;

/**
 * A construct to organize validation of a password policy.
 *
 * @package Drupal\password_policy
 */
class PasswordPolicyValidation {

  /**
   * Error message.
   *
   * @var string
   */
  protected $error = '';

  /**
   * Whether or not policy has an error.
   *
   * @var bool
   */
  protected $valid = TRUE;

  /**
   * Set error message and mark as invalid.
   *
   * @param string $error
   *   The error message.
   */
  public function setErrorMessage($error) {
    $this->valid = FALSE;
    $this->error = $error;
  }

  /**
   * Output error message.
   *
   * @return string
   *   A message representing the error message of the policy's constraints.
   */
  public function getErrorMessage() {
    return $this->error;
  }

  /**
   * Output validation state.
   *
   * @return bool
   *   Whether or not the policy has an error.
   */
  public function isValid() {
    return $this->valid;
  }

}
