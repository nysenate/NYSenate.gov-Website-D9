<?php

namespace Drupal\views_send;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure update settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_send_settingsform';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_send.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_send.settings');

    $throttle_values = array(1, 10, 20, 30, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000);
    $throttle = array_combine($throttle_values, $throttle_values);
    $throttle += array($this->t('Unlimited'));

    $throttle_desc = $this->t('Sets the numbers of messages sent per cron run. Failure to send will also be counted. Cron execution must not exceed the PHP maximum execution time of %max seconds.',
      array('%max' => ini_get('max_execution_time')));
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $throttle_desc .= ' ' . $this->t('You find the time spent to send e-mails in the <a href="@dblog-url">recent log messages</a>.',
        array('@dblog-url' => Url::fromRoute('dblog.overview')->toString()));
    }
    $form['throttle'] = array(
      '#type' => 'select',
      '#title' => $this->t('Cron throttle'),
      '#options' => $throttle,
      '#default_value' => $config->get('throttle'),
      '#description' => $throttle_desc,
    );

    $retry_values = array(0, 1, 2, 3, 4, 5, 10, 15, 20, 30, 40, 50, 100);
    $retry = array_combine($retry_values, $retry_values);
    $form['retry'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of retries'),
      '#options' => $retry,
      '#default_value' => $config->get('retry'),
      '#description' => $this->t('How many retries should be done before a message (in the spool) should be discarded?'),
    );

    $form['spool_expire'] = array(
      '#type' => 'select',
      '#title' => $this->t('Mail spool expiration'),
      '#options' => array(0 => $this->t('Immediate'), 1 => $this->t('1 day'), 7 => $this->t('1 week'), 14 => $this->t('2 weeks')),
      '#default_value' => $config->get('spool_expire'),
      '#description' => $this->t('E-mails are spooled. How long must messages be retained in the spool after successfull sending.'),
    );

    $form['debug'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log e-mails'),
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('When checked all outgoing messages are logged in the system log. A logged e-mail does not guarantee that it is sent or will be delivered. It only indicates that a message is sent to the PHP mail() function. No status information is available of delivery by the PHP mail() function.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('views_send.settings');

    $config
      ->set('throttle', $form_state->getValue('throttle'))
      ->set('retry', $form_state->getValue('retry'))
      ->set('spool_expire', $form_state->getValue('spool_expire'))
      ->set('debug', $form_state->getValue('debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
