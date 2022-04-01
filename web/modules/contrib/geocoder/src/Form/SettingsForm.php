<?php

namespace Drupal\geocoder\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The geocoder settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The typed config service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager
  ) {
    parent::__construct($config_factory);
    $this->typedConfigManager = $typedConfigManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geocoder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['geocoder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('geocoder.settings');

    $geocoder_config_schema = $this->typedConfigManager->getDefinition('geocoder.settings') + ['mapping' => []];
    $geocoder_config_schema = $geocoder_config_schema['mapping'];

    // Attach Geofield Map Library.
    $form['#attached']['library'] = [
      'geocoder/general',
    ];

    $form['geocoder_presave_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $geocoder_config_schema['geocoder_presave_disabled']['label'],
      '#description' => $geocoder_config_schema['geocoder_presave_disabled']['description'],
      '#default_value' => $config->get('geocoder_presave_disabled'),
    ];

    $form['cache'] = [
      '#type' => 'checkbox',
      '#title' => $geocoder_config_schema['cache']['label'],
      '#description' => $geocoder_config_schema['cache']['description'],
      '#default_value' => $config->get('cache'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get all the form state values, in an array structure.
    $form_state_values = $form_state->getValues();

    $config = $this->config('geocoder.settings');
    $config->set('geocoder_presave_disabled', $form_state_values['geocoder_presave_disabled']);
    $config->set('cache', $form_state_values['cache']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
