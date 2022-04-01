<?php

namespace Drupal\address_autocomplete\Plugin\AddressProvider;

use Drupal\address_autocomplete\Plugin\AddressProviderBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines a PostCh plugin for address_autocomplete
 *
 * @AddressProvider(
 *   id = "post_ch",
 *   label = @Translation("Post CH"),
 * )
 */
class PostCh extends AddressProviderBase {

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'endpoint' => '',
        'username' => '',
        'password' => '',
      ];
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => t('post.ch API endpoint'),
      '#default_value' => $this->configuration['endpoint'],
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('post.ch API username'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => t('post.ch API password'),
      '#default_value' => $this->configuration['password'],
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $configuration['endpoint'] = $form_state->getValue('endpoint');
    $configuration['username'] = $form_state->getValue('username');
    $configuration['password'] = $form_state->getValue('password');

    $this->setConfiguration($configuration);
  }

  /**
   * @inheritDoc
   */
  public function processQuery($string) {
    $addresses = $this->prepareRequest($string);
    $results = [];

    foreach ($addresses as $address) {
      $street_name = Html::escape($address->Streetname);
      $house_number = Html::escape($address->HouseNumber);
      $zip_code = Html::escape($address->Zipcode);
      $town_name = Html::escape($address->TownName);

      $results[] = [
        'street_name' => $street_name . " " . $house_number,
        'zip_code' => $zip_code,
        'town_name' => $town_name,
        'label' => $street_name . " " . $house_number . " " . $zip_code . " " . $town_name,
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRequest($string) {
    $request = [
      'request' => [
        'Onrp' => 0,
        'Zipcode' => '',
        'ZipAddition' => '',
        'TownName' => '',
        'StrId' => 0,
        'Streetname' => $string,
        'HouseKey' => 0,
        'HouseNumber' => '',
        'HouseNumberAddition' => '',
      ],
      'zipOrderMode' => 0,
    ];

    // if last entered word starts with number, let's guess it's house number
    // ie: Schultheissenstrasse 2b
    $pieces = explode(' ', $string);
    $pos_number = array_pop($pieces);

    if (!empty($pieces) && is_numeric($pos_number[0])) {
      $request['request']['Streetname'] = implode(' ', $pieces);
      $request['request']['HouseNumber'] = $pos_number;
    }

    $results = $this->request($request);

    // sometimes guessing may be wrong, as numbers could be part of streetname
    // ie: Avenue 14-Avril
    // do fallback here
    if (empty($results) && !empty($request['request']['HouseNumber'])) {
      $request['request']['Streetname'] = $string;
      $request['request']['HouseNumber'] = '';
      $results = $this->request($request);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function request($request) {
    try {
      $response = $this->client->post(
        $this->configuration['endpoint'],
        [
          'auth' => [
            $this->configuration['username'],
            $this->configuration['password'],
          ],
          'body' => json_encode($request),
          'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
          ],
        ]
      );
      $content = json_decode($response->getBody()->getContents());
      $results = $content->QueryAutoComplete2Result->AutoCompleteResult;
      // limit number of results to 10
      $results = array_slice($results, 0, 10);
    } catch (RequestException $e) {
      watchdog_exception('address_autocomplete', $e);
      $results = [];
    }

    return $results;
  }

}
