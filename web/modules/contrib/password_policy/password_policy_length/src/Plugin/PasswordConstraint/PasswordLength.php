<?php

namespace Drupal\password_policy_length\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\user\UserInterface;

/**
 * Enforces a specific character length for passwords.
 *
 * @PasswordConstraint(
 *   id = "password_length",
 *   title = @Translation("Password character length"),
 *   description = @Translation("Verifying that a password has a minimum character length"),
 *   errorMessage = @Translation("The length of your password is too short.")
 * )
 */
class PasswordLength extends PasswordConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $configuration = $this->getConfiguration();
    $validation = new PasswordPolicyValidation();
    switch ($configuration['character_operation']) {
      case 'minimum':
        if (strlen($password) < $configuration['character_length']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_length'], 'Password length must be at least 1 character.', 'Password length must be at least @count characters.'));
        }
        break;

      case 'maximum':
        if (strlen($password) > $configuration['character_length']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_length'], 'Password length must not exceed 1 character.', 'Password length must not exceed @count characters.'));
        }
        break;
    }
    return $validation;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'character_length' => 1,
      'character_operation' => 'minimum',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['character_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of characters'),
      '#default_value' => $this->getConfiguration()['character_length'],
    ];
    $form['character_operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => [
        'minimum' => $this->t('Minimum length'),
        'maximum' => $this->t('Maximum length'),
      ],
      '#default_value' => $this->getConfiguration()['character_operation'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('character_length')) or $form_state->getValue('character_length') <= 0) {
      $form_state->setErrorByName('character_length', $this->t('The character length must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['character_length'] = $form_state->getValue('character_length');
    $this->configuration['character_operation'] = $form_state->getValue('character_operation');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    switch ($this->configuration['character_operation']) {
      case 'minimum':
        $operation = $this->t('at least');
        break;

      case 'maximum':
        $operation = $this->t('at most');
        break;
    }

    return $this->formatPlural($this->configuration['character_length'], 'Password character length of @operation 1 character', 'Password character length of @operation @characters characters', [
      '@operation' => $operation,
      '@characters' => $this->configuration['character_length'],
    ]);
  }

}
