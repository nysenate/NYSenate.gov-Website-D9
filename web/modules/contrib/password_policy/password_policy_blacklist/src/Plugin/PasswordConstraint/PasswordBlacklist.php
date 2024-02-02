<?php

namespace Drupal\password_policy_blacklist\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;

/**
 * Ensures the password doesn't contain any restricted words or phrases.
 *
 * @PasswordConstraint(
 *   id = "password_blacklist",
 *   title = @Translation("Password Blacklist"),
 *   description = @Translation("Password cannot match certain disallowed passwords"),
 *   error_message = @Translation("Your password contains restricted words or phrases.")
 * )
 */
class PasswordBlacklist extends PasswordConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function validate($password, $user_context) {
    $config = $this->getConfiguration();
    $validation = new PasswordPolicyValidation();

    // Parse the blacklist values.
    $blacklisted_passwords = $config['blacklist'];
    $blacklisted_passwords = array_map('trim', $blacklisted_passwords);
    $blacklisted_passwords = array_filter($blacklisted_passwords, 'strlen');

    // Check password against blacklisted values.
    foreach ($blacklisted_passwords as $blacklisted_password) {
      if ($config['match_substrings'] && stripos($password, $blacklisted_password) !== FALSE) {
        $validation->setErrorMessage($this->t('There are restricted terms in your password. Please modify your password.'));
        break;
      }
      elseif (strcasecmp($password, $blacklisted_password) == 0) {
        $validation->setErrorMessage($this->t('Your password is on the blacklist. Please choose a different password.'));
        break;
      }
    }

    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'blacklist' => [''],
      'match_substrings' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get configuration.
    $config = $this->getConfiguration();

    $form['match_substrings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Also disallow passwords containing blacklisted passwords'),
      '#default_value' => $config['match_substrings'],
    ];

    $form['blacklist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blacklisted passwords'),
      '#description' => $this->t('Password cannot be a member of this list, ignoring case. Enter one password per line.'),
      '#default_value' => implode("\r\n", $config['blacklist']),
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
    $this->configuration['blacklist'] = explode("\r\n", $form_state->getValue('blacklist'));
    $this->configuration['match_substrings'] = $form_state->getValue('match_substrings');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    if ($this->configuration['match_substrings']) {
      return $this->t('Password must not contain any restricted words or phrases.');
    }
    else {
      return $this->t('Password must not be on the blacklist.');
    }
  }

}
