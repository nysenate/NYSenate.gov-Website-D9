<?php

namespace Drupal\login_history\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for the login_history module.
 *
 * @package Drupal\login_history\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_history.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_history_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('login_history.settings');

    $form['history_keep'] = [
      '#type' => 'details',
      '#title' => $this->t('Login History Events to Keep'),
      '#description' => $this->t('Per User is the default if both boxes have numbers above 0 entered.'),
      '#open' => TRUE,
    ];
    $form['history_keep']['keep_user'] = [
      '#type' => 'number',
      '#min' => 0,
      '#size' => 3,
      '#title' => $this->t('Per User'),
      '#default_value' => $config->get('keep_user'),
      '#description' => $this->t('Total number of login histories to keep per user. Enter 0 to keep all records.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('login_history.settings');

    if ($form['history_keep']['keep_user']['#default_value'] != $form_state->getValue('keep_user')) {
      $config->set('keep_user', $form_state->getValue('keep_user'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
