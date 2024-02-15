<?php

namespace Drupal\password_policy_characters\Plugin\PasswordConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\password_policy\PasswordConstraintBase;
use Drupal\password_policy\PasswordPolicyValidation;
use Drupal\user\UserInterface;

/**
 * Enforces a number of a type of character in passwords.
 *
 * @PasswordConstraint(
 *   id = "password_policy_character_constraint",
 *   title = @Translation("Password character type"),
 *   description = @Translation("Verifying that a password has a specific number of characters"),
 *   errorMessage = @Translation("The password does not contain a the correct number of certain characters.")
 * )
 */
class PasswordCharacter extends PasswordConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function validate($password, UserInterface $user) {
    $configuration = $this->getConfiguration();
    $validation = new PasswordPolicyValidation();
    $character_distribution = count_chars($password);

    $count_upper = 0;
    $count_lower = 0;
    $count_letter = 0;
    $count_special = 0;
    $count_numeric = 0;

    foreach ($character_distribution as $i => $val) {
      if ($val) {
        $char = chr($i);
        if (is_numeric($char)) {
          $count_numeric++;
        }
        else {
          if (ctype_upper($char)) {
            $count_upper++;
            $count_letter++;
          }
          else {
            if (ctype_lower($char)) {
              $count_lower++;
              $count_letter++;
            }
            else {
              $count_special++;
            }
          }
        }
      }
    }

    switch ($configuration['character_type']) {
      case 'uppercase':
        if ($count_upper < $configuration['character_count']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_count'], 'Password must contain at least 1 uppercase character.', 'Password must contain at least @count unique uppercase characters.'));
        }
        break;

      case 'lowercase':
        if ($count_lower < $configuration['character_count']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_count'], 'Password must contain at least 1 lowercase character.', 'Password must contain at least @count unique lowercase characters.'));
        }
        break;

      case 'letter':
        if ($count_letter < $configuration['character_count']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_count'], 'Password must contain at least 1 letter character.', 'Password must contain at least @count unique letter characters.'));
        }
        break;

      case 'special':
        if ($count_special < $configuration['character_count']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_count'], 'Password must contain at least 1 special character.', 'Password must contain at least @count unique special characters.'));
        }
        break;

      case 'numeric':
        if ($count_numeric < $configuration['character_count']) {
          $validation->setErrorMessage($this->formatPlural($configuration['character_count'], 'Password must contain at least 1 numeric character.', 'Password must contain at least @count unique numeric characters.'));
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
      'character_count' => 1,
      'character_type' => 'special',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['character_count'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of characters'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['character_count'],
    ];
    $form['character_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Character type'),
      '#required' => TRUE,
      '#options' => [
        'uppercase' => $this->t('Uppercase'),
        'lowercase' => $this->t('Lowercase'),
        'letter' => $this->t('Letter'),
        'numeric' => $this->t('Numeric'),
        'special' => $this->t('Special Character'),
      ],
      '#default_value' => $this->getConfiguration()['character_type'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('character_count')) or $form_state->getValue('character_count') < 0) {
      $form_state->setErrorByName('character_count', $this->t('The number of characters must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['character_count'] = $form_state->getValue('character_count');
    $this->configuration['character_type'] = $form_state->getValue('character_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $summary = "";
    switch ($configuration['character_type']) {
      case 'uppercase':
        $summary = $this->formatPlural($configuration['character_count'],
          'Password must contain at least 1 uppercase character.',
          'Password must contain at least @count unique uppercase characters.'
        );
        break;

      case 'lowercase':
        $summary = $this->formatPlural($configuration['character_count'],
          'Password must contain at least 1 lowercase character.',
          'Password must contain at least @count unique lowercase characters.'
        );
        break;

      case 'letter':
        $summary = $this->formatPlural($configuration['character_count'],
          'Password must contain at least 1 letter character.',
          'Password must contain at least @count unique letter characters.'
        );
        break;

      case 'special':
        $summary = $this->formatPlural($configuration['character_count'],
          'Password must contain at least 1 special character.',
          'Password must contain at least @count unique special characters.'
        );
        break;

      case 'numeric':
        $summary = $this->formatPlural($configuration['character_count'],
          'Password must contain at least 1 numeric character.',
          'Password must contain at least @count unique numeric characters.'
        );
        break;
    }
    return $summary;
  }

}
