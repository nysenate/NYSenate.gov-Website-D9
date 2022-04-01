<?php

namespace Drupal\address_autocomplete\Plugin;

use Drupal\address_autocomplete\Form\ProviderSettingsForm;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Address provider plugins.
 */
abstract class AddressProviderBase extends PluginBase implements AddressProviderInterface {

  /**
   * @inheritDoc
   */
  protected $client;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->client = new Client();
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Swap config with a values from config object.
    $configObject = $container->get('config.factory')
      ->get(ProviderSettingsForm::$configName);
    if ($configurationSerialized = $configObject->get($plugin_id)) {
      $configuration = unserialize($configurationSerialized);
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return [
      'plugin_id' => $this->pluginId,
    ];
  }

  /**
   * @inheritDoc
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * @inheritDoc
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
