<?php

namespace Drupal\security_review\CheckSettings;

use Drupal\security_review\CheckSettings;

/**
 * Provides the settings form for the View Access check.
 */
class ViewSettings extends CheckSettings {

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = [];
    $ignore_default = $this->get('ignore_default', FALSE);
    $form['ignore_default'] = [
      '#type' => 'checkbox',
      '#title' => t('Ignore default view'),
      '#description' => t('Check to ignore default views.'),
      '#default_value' => $ignore_default,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array $values) {
    $this->set('ignore_default', $values['ignore_default']);
  }

}
