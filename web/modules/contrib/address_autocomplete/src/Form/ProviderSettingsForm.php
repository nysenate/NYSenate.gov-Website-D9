<?php

namespace Drupal\address_autocomplete\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProviderSettingsForm extends ConfigFormBase {

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'address_autocomplete.settings';

  /**
   * @inheritDoc
   */
  protected $providerManager;

  /**
   * @inheritDoc
   */
  public function __construct(PluginManagerInterface $provider_manager) {
    $this->providerManager = $provider_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address_provider')
    );
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return [ProviderSettingsForm::$configName];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'address_autocomplete_settings_form_' . $this->getPluginIdFromRequest();
  }

  /**
   * @inheritDoc
   */
  protected function getPluginIdFromRequest() {
    $request = $this->getRequest();
    return $request->get('_plugin_id');
  }

  /**
   * @inheritDoc
   */
  public function getPluginInstance($plugin_id) {
    return $this->providerManager->createInstance($plugin_id);
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $form = $instance->buildConfigurationForm($form, $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $instance->validateConfigurationForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $instance->submitConfigurationForm($form, $form_state);
    $config = $this->config(ProviderSettingsForm::$configName);
    $instanceConfiguration = $instance->getConfiguration();
    $config->set($plugin_id, serialize($instanceConfiguration));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
