<?php

namespace Drupal\nys_sage\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_sage\Logger\SageLogger;

/**
 * Admin config form for nys_sage.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Local configuration copy.
   *
   * @var \Drupal\Core\Config\Config
   */
  private Config $localConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->localConfig = $this->config('nys_sage.settings');
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'nys_sage_config';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Need to get the immutable config to see overrides.
    $apikey = $this->configFactory->get('nys_sage.settings')->get('api_key');
    $apikey_text = $apikey
        ? "An API key is already saved.  Leave the box blank to keep it, or input a new one to change it."
        : "<h2><b>No API key has been configured.  SAGE calls will fail until one is provided.</b></h2>";
    $apikey_required = !((boolean) $apikey);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => '',
      '#description' => $apikey_text,
      '#required' => $apikey_required,
    ];
    $form['use_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSL'),
      '#default_value' => $this->localConfig->get('use_ssl') ?? TRUE,
      '#description' => $this->t('Forces API calls to use SSL.'),
    ];
    $form['ssl_verify_peer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify SSL peer'),
      '#default_value' => $this->localConfig->get('ssl_verify_peer') ?? TRUE,
      '#description' => $this->t('Uncheck this box to relax peer verification.  This should be left on whenever possible.'),
    ];
    $form['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SAGE Base URI'),
      '#default_value' => $this->localConfig->get('api_endpoint') ?? '',
      '#description' => $this->t('The host and base path for the SAGE API.  The group and method should not appear here.'),
    ];
    $form['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging'),
      '#default_value' => $this->localConfig->get('logging') ?? 0,
      '#description' => $this->t('Enables explicit logging for SAGE requests and responses.'),
    ];
    $form['logging_warning'] = [
      '#children' => $this->t('Logging SAGE calls can generate a significant amount of data very quickly.  It should be left off unless debugging is necessary, and a maximum retention time (see below) should be set if it is enabled.'),
      '#type' => 'container',
      '#attributes' => ['class' => ['warning']],
      '#states' => [
        'visible' => [':input[name="logging"]' => ['checked' => TRUE]],
      ],
    ];
    $form['maximum_retention'] = [
      '#type' => 'textfield',
      '#size' => 5,
      '#title' => 'Maximum Retention',
      '#field_suffix' => $this->t('days'),
      '#description' => $this->t('The maximum number of days to keep a record in the sage log.  Set to zero to keep forever.'),
      '#default_value' => $this->localConfig->get('maximum_retention') ?? 15,
      '#states' => [
        'visible' => [':input[name="logging"]' => ['checked' => TRUE]],
      ],
    ];
    $form['actions']['submit_truncate'] = [
      '#type' => 'submit',
      '#value' => 'Save and Truncate',
      '#submit' => ['::truncateLog', '::submitForm'],
      '#weight' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['api_key'])) {
      $this->localConfig->set('api_key', Html::escape($values['api_key']));
    }
    $this->localConfig
      ->set('use_ssl', $values['use_ssl'])
      ->set('ssl_verify_peer', $values['ssl_verify_peer'])
      ->set('api_endpoint', $values['api_endpoint'])
      ->set('logging', $values['logging'])
      ->set('maximum_retention', $values['maximum_retention'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for truncating the SAGE logging table.
   */
  public function truncateLog() {
    SageLogger::truncate();
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_sage.settings'];
  }

}
