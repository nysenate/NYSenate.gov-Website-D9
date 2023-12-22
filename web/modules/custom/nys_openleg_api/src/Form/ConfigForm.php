<?php

namespace Drupal\nys_openleg_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for nys_openleg_api module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * A shortcut to the nys_openleg_api.settings config collection.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $localConfig;

  /**
   * The immutable version of nys_openleg_api.settings.  (Includes overrides)
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $configOverrides;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ImmutableConfig $config) {
    $this->configOverrides = $config;
    parent::__construct($config_factory);

    $this->localConfig = $this->config('nys_openleg_api.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('openleg_api.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openleg_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached'] = ['library' => ['nys_openleg_api/openleg_api']];

    // Check for an API key override.  Need to use the immutable config.
    $apikey = $this->configOverrides->get('api_key');

    // Get the editable API key (no overrides), and set the text accordingly.
    $saved_apikey = $this->localConfig->get('api_key');
    $apikey_text = $saved_apikey
      ? "<div>An API key is already saved.  Leave the box blank to keep it, or input a new one to change it.</div>"
      : "<h2><b>No API key has been configured.  The API key is required before making calls to OpenLeg API.</b></h2>";
    if ($apikey && ($apikey != $saved_apikey)) {
      $apikey_text .= '<h3 class="nys-openleg-config-warning">An override is configured to replace this setting.</h3>';
    }
    $apikey_required = !($apikey);

    $form['api_key'] = [
      '#type' => 'password',
      '#required' => $apikey_required,
      '#title' => 'OpenLeg API Key',
      '#description' => $apikey_text,
      '#default_value' => '',
    ];

    $form['log_level'] = [
      '#type' => 'select',
      '#title' => 'Log Level',
      '#description' => 'Determines the level of logging for API activity.',
      '#options' => RfcLogLevel::getLevels(),
      '#default_value' => $this->localConfig->get('log_level') ?? RfcLogLevel::NOTICE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    // Set the API key.
    if (!empty($values['api_key'])) {
      $this->localConfig->set('api_key', Html::escape($values['api_key']));
    }

    // Set the log level.
    if (($level = ($values['log_level'] ?? FALSE)) !== FALSE) {
      $this->localConfig->set('log_level', (int) $level);
    }

    $this->localConfig->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['nys_openleg_api.settings'];
  }

}
