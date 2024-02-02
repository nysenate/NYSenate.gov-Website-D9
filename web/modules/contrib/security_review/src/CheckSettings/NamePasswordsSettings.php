<?php

namespace Drupal\security_review\CheckSettings;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\security_review\CheckSettings;

/**
 * Provides the settings form for the Name/Passwords check.
 */
class NamePasswordsSettings extends CheckSettings {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = [];
    $ignore_default = $this->get('number_of_users', 100);
    $form['number_of_users'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of user chunks'),
      '#description' => $this->t('Number of users to load at one time.'),
      '#default_value' => $ignore_default,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array $values) {
    $this->set('number_of_users', intval($values['number_of_users']));
  }

}
