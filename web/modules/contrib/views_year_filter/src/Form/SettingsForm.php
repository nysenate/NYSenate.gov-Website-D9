<?php

namespace Drupal\views_year_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Views year filter config form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_year_filter.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_year_filter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_year_filter.settings');

    $form['use_bootstrap_datepicker'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use Bootstrap Datepicker'),
      '#description'   => $this->t('Check this option to use Bootstrap Datepicker popup for date filter.'),
      '#default_value' => $config->get('use_bootstrap_datepicker'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('views_year_filter.settings')
      ->set('use_bootstrap_datepicker', $form_state->getValue('use_bootstrap_datepicker'))
      ->save();
    $this->messenger()->addWarning($this->t('This config change will take effect after next cache clear!'));
  }

}
