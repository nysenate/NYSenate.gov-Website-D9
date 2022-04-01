<?php

namespace Drupal\password_policy_username\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\user\UserInterface;

/**
 * Ensures the password doesn't contain the username.
 *
 * @PasswordConstraint(
 *   id = "password_username",
 *   title = @Translation("Password username"),
 *   description = @Translation("Password must not contain their username"),
 *   errorMessage = @Translation("Your password contains your username.")
 * )
 */
class PasswordUsername extends PasswordConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $config = $this->getConfiguration();
    $validation = new PasswordPolicyValidation();

    if ($config['disallow_username'] && stripos($password, $user->getAccountName()) !== FALSE) {
      $validation->setErrorMessage($this->t('Password must not contain the username.'));
    }

    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'disallow_username' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get configuration.
    $config = $this->getConfiguration();

    $form['disallow_username'] = [
      '#type' => 'hidden',
      '#value' => $config['disallow_username'],
    ];

    $form['disallow_username_message'] = [
      '#type' => 'description',
      '#markup' => $this->t('Prevent user from having a password containing their username.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['disallow_username'] = $form_state->getValue('disallow_username');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t("Password must not contain the user's username.");
  }

}
