<?php

namespace Drupal\security_review\CheckSettings;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\security_review\CheckSettings;

/**
 * Provides the settings form for the View Access check.
 */
class ViewSettings extends CheckSettings {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = [];
    $ignore_default = $this->get('ignore_default', FALSE);
    $form['ignore_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore default view'),
      '#description' => $this->t('Check to ignore default views.'),
      '#default_value' => $ignore_default,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array $values) {
    $this->set('ignore_default', boolval($values['ignore_default']));
  }

}
