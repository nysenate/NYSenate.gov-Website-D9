<?php

namespace Drupal\job_scheduler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'job_scheduler_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['job_scheduler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('job_scheduler.settings');

    $form['rebuild']['description'] = [
      '#markup' => '<p>' . $this->t('Rebuilds scheduled information.') . '</p>',
    ];

    $form['rebuild']['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild'),
      '#submit' => ['::rebuild'],
    ];

    $form['settings'] = [
      '#title' => $this->t('Settings'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['settings']['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Detailed job scheduler logging'),
      '#default_value' => $config->get('logging'),
      '#description' => $this->t('Run time info of jobs will be written to watchdog.'),
    ];

    $form['settings']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Jobs limit'),
      '#min' => 1,
      '#default_value' => $config->get('limit'),
      '#description' => $this->t('The number of jobs to perform in one run. Defaults to 200'),
    ];

    $form['settings']['time'] = [
      '#type' => 'number',
      '#title' => $this->t('Executing time'),
      '#min' => 1,
      '#default_value' => $config->get('time'),
      '#description' => $this->t('How much time scheduler should spend on processing jobs in seconds. Defaults to 30'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('job_scheduler.settings')
      ->set('logging', $form_state->getValue('logging'))
      ->set('limit', $form_state->getValue('limit'))
      ->set('time', $form_state->getValue('time'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Form submission handler for rebuild scheduled information.
   */
  public function rebuild(array &$form, FormStateInterface $form_state) {
    job_scheduler_rebuild_all();
    $this->messenger()->addStatus($this->t('Rebuilded successfully.'));
  }

}
