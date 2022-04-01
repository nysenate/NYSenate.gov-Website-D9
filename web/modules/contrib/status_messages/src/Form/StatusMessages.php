<?php

namespace Drupal\status_messages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StatusMessages.
 *
 * @package Drupal\status_messages\Form
 */
class StatusMessages extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['status_messages.status_messages'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'status_messages_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('status_messages.status_messages');
    $form['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
    ];
    $options = [
      5000 => $this->t('5 Seconds'),
      10000 => $this->t('10 Seconds'),
      15000 => $this->t('15 Seconds'),
      20000 => $this->t('20 Seconds'),
      3600000 => $this->t('Never'),
    ];
    $form['configuration']['time'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Time'),
      '#default_value' => $config->get('time'),
      '#description' => $this->t('Close status message automatically after above seconds.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('status_messages.status_messages')
      ->set('time', trim($form_state->getValue('time')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
