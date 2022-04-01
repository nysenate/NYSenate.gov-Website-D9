<?php

namespace Drupal\address_autocomplete\Plugin\AddressProvider;

use Drupal\address_autocomplete\Plugin\AddressProviderBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a GoogleMaps plugin for address_autocomplete
 *
 * @AddressProvider(
 *   id = "google_maps",
 *   label = @Translation("Google Maps"),
 * )
 */
class GoogleMaps extends AddressProviderBase {

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'api_key' => '',
      ];
  }

  /**
   * @inheritDoc
   */
  public function processQuery($string) {
    $results = [];

    $url = 'https://maps.googleapis.com/maps/api/geocode/json';
    $query = [
      'key' => $this->configuration['api_key'],
      'address' => $string,
      'language' => 'en',
    ];

    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (!empty($content["error_message"])) {
      return $results;
    }

    foreach ($content["results"] as $key => $result) {
      foreach ($result["address_components"] as $component) {
        switch ($component["types"][0]) {
          case "street_number":
            $streetNumber = $component["long_name"];
            break;
          case "route":
            $results[$key]["street_name"] = $component["long_name"];
            break;
          case "administrative_area_level_1":
            $results[$key]["town_name"] = $component["long_name"];
            break;
          case "postal_code":
            $results[$key]["zip_code"] = $component["long_name"];
            break;
        }
      }
      $results[$key]["street_name"] .= !empty($streetNumber) ? " " . $streetNumber : "";
      $results[$key]["label"] = $result["formatted_address"];
    }

    return $results;
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#default_value' => $this->configuration['api_key'],
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
    $configuration['api_key'] = $form_state->getValue('api_key');
    $this->setConfiguration($configuration);
  }

}