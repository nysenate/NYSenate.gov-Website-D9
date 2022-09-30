<?php

namespace Drupal\password_policy_consecutive\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\user\UserInterface;

/**
 * Enforces a maximum number of consecutive identical characters.
 *
 * @PasswordConstraint(
 *   id = "consecutive",
 *   title = @Translation("Consecutive characters"),
 *   description = @Translation("Verifying that a password has a maximum number of consecutive identical characters."),
 *   errorMessage = @Translation("Your password has too many consecutive characters.")
 * )
 */
class ConsecutiveCharacters extends PasswordConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $validation = new PasswordPolicyValidation();
    $max = $this->getConfiguration()['max_consecutive_characters'];
    if ($max < 2) {
      $validation->setErrorMessage($this->t('Invalid plugin configuration.'));
    }
    $pattern = '/(.)\1{' . ($max - 1) . '}/';
    if (preg_match($pattern, $password)) {
      if ($max == 2) {
        $validation->setErrorMessage($this->t('No consecutive identical characters are allowed.'));
      }
      else {
        $validation->setErrorMessage($this->t('Password must have fewer than @max consecutive identical characters. This is case sensitive.', ['@max' => $max]));
      }
    }
    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'max_consecutive_characters' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $max = $this->getConfiguration()['max_consecutive_characters'];
    if ($max == 2) {
      return $this->t('No consecutive identical characters are allowed.');
    }
    return $this->t('Consecutive identical characters fewer than: @max', ['@max' => $max]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $range = range(2, 5);
    $form['max_consecutive_characters'] = [
      '#type' => 'select',
      '#options' => array_combine(array_values($range), $range),
      '#title' => $this->t('Consecutive identical characters fewer than'),
      '#description' => $this->t('This many or more consecutive identical characters will not be allowed in the password.'),
      '#default_value' => $this->getConfiguration()['max_consecutive_characters'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $types = $form_state->getValue('max_consecutive_characters');
    if (!is_numeric($types) || $types < 2) {
      $form_state->setErrorByName('max_consecutive_characters', $this->t('The number of consecutive identical characters must be higher than 1 otherwise all passwords will fail.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['max_consecutive_characters'] = $form_state->getValue('max_consecutive_characters');
  }

}
