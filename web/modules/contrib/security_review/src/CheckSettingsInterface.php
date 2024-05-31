<?php

namespace Drupal\security_review;

/**
 * Interface for check-specific settings and forms for altering them.
 */
interface CheckSettingsInterface {

  /**
   * Gets a check-specific setting value identified by $key.
   *
   * @param string $key
   *   The key.
   * @param mixed $default_value
   *   Default value to return in case $key does not exist.
   *
   * @return mixed
   *   The value of the stored setting.
   */
  public function get($key, $default_value);

  /**
   * Sets a check-specific setting value identified by $key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The new value.
   *
   * @return CheckSettingsInterface
   *   Returns itself.
   */
  public function set($key, $value);

  /**
   * Form constructor.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm();

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $values
   *   The current values of the form.
   */
  public function validateForm(array &$form, array $values);

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $values
   *   The current values of the form.
   */
  public function submitForm(array &$form, array $values);

}
