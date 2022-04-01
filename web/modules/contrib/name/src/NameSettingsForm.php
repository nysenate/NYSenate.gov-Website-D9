<?php

namespace Drupal\name;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure name settings for this site.
 */
class NameSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'name_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['name.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'name', 'name.admin');

    $config = $this->configFactory->get('name.settings');

    $form['name_settings'] = ['#tree' => TRUE];
    $form['name_settings']['sep1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator 1 replacement token'),
      '#default_value' => $config->get('sep1'),
    ];
    $form['name_settings']['sep2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator 2 replacement token'),
      '#default_value' => $config->get('sep2'),
    ];
    $form['name_settings']['sep3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator 3 replacement token'),
      '#default_value' => $config->get('sep3'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('name.settings')
      ->set('sep1', $form_state->getValue(['name_settings', 'sep1']))
      ->set('sep2', $form_state->getValue(['name_settings', 'sep2']))
      ->set('sep3', $form_state->getValue(['name_settings', 'sep3']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
