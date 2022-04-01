<?php

namespace Drupal\security_review;

use Drupal\Core\Config\Config;

/**
 * Defines the default implementation of CheckSettingsInterface.
 */
class CheckSettings implements CheckSettingsInterface {

  /**
   * The parent check.
   *
   * @var \Drupal\security_review\Check
   */
  protected $check;

  /**
   * The configuration storage of the parent Check.
   *
   * @var \Drupal\Core\Config\Config $config
   */
  protected $config;

  /**
   * Creates a CheckSettings instance.
   *
   * @param \Drupal\security_review\Check $check
   *   The parent Check.
   * @param \Drupal\Core\Config\Config $config
   *   The parent Check's configuration.
   */
  public function __construct(Check $check, Config &$config) {
    $this->check = $check;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default_value = NULL) {
    $value = $this->config->get('settings.' . $key);

    if ($value == NULL) {
      return $default_value;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->config->set('settings.' . $key, $value);
    $this->config->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array $values) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array $values) {
    // Handle submission.
  }

}
