<?php

namespace Drupal\address_autocomplete\Controller;

use Drupal\address_autocomplete\Form\SettingsForm;
use Drupal\address_autocomplete\Plugin\AddressProviderManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for watches autocomplete form elements.
 *
 * @todo try and restrict direct access somehow... Drupal's CSRF token does not
 *   work with Anonymous user... Maybe custom CSRF token / custom check?
 */
class AddressAutocomplete extends ControllerBase {

  /**
   * @inheritDoc
   */
  protected $config;

  /**
   * @inheritDoc
   */
  protected $providerManager;

  /**
   * @inheritDoc
   */
  public function __construct(ConfigFactoryInterface $config_factory, AddressProviderManager $provider_manager) {
    $this->config = $config_factory->get(SettingsForm::$configName);
    $this->providerManager = $provider_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.address_provider')
    );
  }

  /**
   * Handler for the autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $input = $request->query->get('q');
    $results = $this->getProviderResults($input);
    return new JsonResponse($results);
  }

  /**
   * @inheritDoc
   */
  public function getProviderResults($string) {
    $plugin_id = $this->config->get('active_plugin');
    $plugin = $this->providerManager->createInstance($plugin_id);
    return $plugin->processQuery($string);
  }

}
