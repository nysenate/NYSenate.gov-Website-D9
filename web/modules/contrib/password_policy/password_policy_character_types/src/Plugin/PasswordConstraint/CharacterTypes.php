<?php

namespace Drupal\password_policy_character_types\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\user\UserInterface;

/**
 * Enforces a minimum number of character types for passwords.
 *
 * @PasswordConstraint(
 *   id = "character_types",
 *   title = @Translation("Password character types"),
 *   description = @Translation("Verifying that a password has a minimum number of character types."),
 *   errorMessage = @Translation("Your password must have different character types.")
 * )
 */
class CharacterTypes extends PasswordConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $validation = new PasswordPolicyValidation();
    $types = $this->getConfiguration()['character_types'];
    if ($types < 2 || $types > 4) {
      $validation->setErrorMessage($this->t('Invalid plugin configuration.'));
    }
    $character_sets = count(array_filter([
      preg_match('/[a-z]/', $password),
      preg_match('/[A-Z]/', $password),
      preg_match('/[0-9]/', $password),
      preg_match('/[^a-zA-Z0-9]/', $password),
    ]));
    if ($character_sets < $types) {
      $validation->setErrorMessage($this->t('Password must contain at least @types types of characters from the following character types: lowercase letters, uppercase letters, digits, special characters.', ['@types' => $types]));
    }
    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'character_types' => 3,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['character_types'] = [
      '#type' => 'select',
      '#options' => [
        '2' => '2',
        '3' => '3',
        '4' => '4',
      ],
      '#title' => $this->t('Minimum number of character types'),
      '#description' => $this->t('Select the minimum number of character types which must be found in a password. The four supported character types are given as: lowercase letters, uppercase letters, digits, special characters.'),
      '#default_value' => $this->getConfiguration()['character_types'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $types = $form_state->getValue('character_types');
    if (!is_numeric($types) || $types < 2 || $types > 4) {
      $form_state->setErrorByName('character_types', $this->t('The number of character types must be between 2 and 4.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['character_types'] = $form_state->getValue('character_types');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Minimum password character types: @types', ['@types' => $this->getConfiguration()['character_types']]);
  }

}
