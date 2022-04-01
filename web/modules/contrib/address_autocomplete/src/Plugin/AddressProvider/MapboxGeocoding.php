<?php

namespace Drupal\address_autocomplete\Plugin\AddressProvider;

use Drupal\address_autocomplete\Plugin\AddressProviderBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a Mapbox Geocoding plugin for address_autocomplete
 *
 * @AddressProvider(
 *   id = "mapbox_geocoding",
 *   label = @Translation("Mapbox Geocoding"),
 * )
 */
class MapboxGeocoding extends AddressProviderBase {

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'token' => '',
      ];
  }

  /**
   * @inheritDoc
   */
  public function processQuery($string) {
    $results = [];

    $token = $this->configuration['token'];
    $url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . $string . '.json?access_token=' . $token . '&autocomplete=true&types=address&limit=10';

    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());

    foreach ($content["features"] as $key => $feature) {
      $results[$key]['street_name'] = $feature["text"];
      $results[$key]['street_name'] .= isset($feature["address"]) ? ' ' . $feature["address"] : '';
      $results[$key]['town_name'] = $feature["context"][1]["text"];
      $results[$key]['zip_code'] = $feature["context"][0]["text"];
      $results[$key]['label'] = $feature["place_name"];
    }

    return $results;
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => t('Token'),
      '#default_value' => $this->configuration['token'],
      '#attributes' => [
        'autocomplete' => 'off',
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $configuration['token'] = $form_state->getValue('token');
    $this->setConfiguration($configuration);
  }

}