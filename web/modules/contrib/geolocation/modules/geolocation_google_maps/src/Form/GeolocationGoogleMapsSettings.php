<?php

namespace Drupal\geolocation_google_maps\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the GeolocationGoogleMapAPIkey form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GeolocationGoogleMapsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geolocation_google_maps.settings');

    $form['#tree'] = TRUE;

    $form['google_map_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $config->get('google_map_api_key'),
      '#description' => $this->t('Google requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here. If you use key module to store the api key, enter the key name here instead.'),
    ];

    $form['google_map_api_server_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Server key'),
      '#default_value' => $config->get('google_map_api_server_key'),
      '#description' => $this->t('If you use a separate key for server-side operations, add it here. Leave empty to use the Google Maps API key as above.'),
    ];

    $custom_parameters = $config->get('google_map_custom_url_parameters');
    $form['parameters'] = [
      '#type' => 'details',
      '#title' => $this->t('Optional Google Parameters'),
      '#description' => $this->t('None of these parameters is required. Please note: modules might extend or override these options.'),
      '#open' => !empty($custom_parameters),
    ];

    $form['parameters']['libraries'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Google Maps API Libraries - 'libraries'"),
      '#description' => $this->t('See <a href=":google_libraries_link">Google libraries documentation</a>.', [':google_libraries_link' => 'https://developers.google.com/maps/documentation/javascript/libraries']),
      '#attributes' => [
        'id' => 'geolocation-google-libraries',
      ],
    ];

    $module_parameters = \Drupal::moduleHandler()->invokeAll('geolocation_google_maps_parameters');

    if (!empty($module_parameters['libraries'])) {
      $module_libraries = array_unique($module_parameters['libraries']);
      $form['parameters']['libraries']['module_defined'] = [
        '#prefix' => $this->t('Module defined library requirements - These libraries will be loaded anyway and should not be listed here.'),
        '#theme' => 'item_list',
        '#items' => $module_libraries,
      ];
    }

    $default_libraries = empty($custom_parameters['libraries']) ? [] : $custom_parameters['libraries'];
    $max = max($form_state->get('fields_count'), count($default_libraries), 0);
    $form_state->set('fields_count', $max);

    // Add elements that don't already exist.
    for ($delta = 0; $delta <= $max; $delta++) {
      if (empty($form['parameters']['libraries'][$delta])) {
        $form['parameters']['libraries'][$delta] = [
          '#type' => 'textfield',
          '#title' => $this->t('Library name'),
          '#default_value' => empty($default_libraries[$delta]) ? '' : $default_libraries[$delta],
        ];
      }
    }

    $form['parameters']['libraries']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add library'),
      '#submit' => [[$this, 'addLibrariesSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addLibrariesCallback'],
        'wrapper' => 'geolocation-google-libraries',
        'effect' => 'fade',
      ],
    ];

    $form['parameters']['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps API Region - 'region'"),
      '#default_value' => empty($custom_parameters['region']) ?: $custom_parameters['region'],
    ];
    $form['parameters']['language'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps API Localization - 'language'"),
      '#default_value' => empty($custom_parameters['language']) ?: $custom_parameters['language'],
      '#description' => $this->t('See <a href=":google_localization_link">Google Maps API - Localizing the Map</a>.', [':google_localization_link' => 'https://developers.google.com/maps/documentation/javascript/localization']),
    ];

    $form['parameters']['v'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps API Version - 'v'"),
      '#default_value' => empty($custom_parameters['v']) ?: $custom_parameters['v'],
      '#description' => $this->t('Will default to current experimental. See <a href=":google_version_link">Google Maps API - Versioning</a>.', [':google_version_link' => 'https://developers.google.com/maps/documentation/javascript/versions']),
    ];

    $form['parameters']['client'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps API Client ID - 'client'"),
      '#default_value' => empty($custom_parameters['client']) ?: $custom_parameters['client'],
      '#description' => $this->t('Attention: setting this option has major usage implications. See <a href=":google_client_id_link">Google Maps Authentication documentation</a>.', [':google_client_id_link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key#client-id']),
    ];

    $form['parameters']['channel'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps API Channel ID - 'channel'"),
      '#default_value' => empty($custom_parameters['channel']) ?: $custom_parameters['channel'],
      '#description' => $this->t('Channel parameter for tracking map usage.'),
    ];

    $form['use_current_language'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use current interface language in Google Maps'),
      '#default_value' => $config->get('use_current_language') ? TRUE : FALSE,
      '#description' => $this->t('If a supported language is set by Drupal, it will be handed over to Google Maps. Defaults to language parameter above if set. List of <a href=":google_languages_list">supported languages here</a>.', [':google_languages_list' => 'https://developers.google.com/maps/faq#languagesupport']),
    ];

    $form['china_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable China mode'),
      '#default_value' => $config->get('china_mode') ? TRUE : FALSE,
      '#description' => $this->t('Use the specific URLs required in the PR China. See explanation at <a href=":google_faq">Google FAQ</a>.', [':google_faq' => 'https://developers.google.com/maps/faq?#china_ws_access']),
    ];

    $form['google_maps_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps Base URL Override'),
      '#default_value' => $config->get('google_maps_base_url'),
      '#description' => $this->t('Override Google Maps URL base entirely.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Add library submit handler.
   *
   * @param array $form
   *   Settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addLibrariesSubmit(array &$form, FormStateInterface &$form_state) {
    $max = $form_state->get('fields_count') + 1;
    $form_state->set('fields_count', $max);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Add library AJAX handler.
   *
   * @param array $form
   *   Settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Ajax return value.
   */
  public function addLibrariesCallback(array &$form, FormStateInterface &$form_state) {
    return $form['parameters']['libraries'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geolocation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'geolocation_google_maps.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('geolocation_google_maps.settings');
    $config->set('google_map_api_key', $form_state->getValue('google_map_api_key'));
    $config->set('google_map_api_server_key', $form_state->getValue('google_map_api_server_key'));

    $config->set('use_current_language', $form_state->getValue('use_current_language'));
    $config->set('china_mode', $form_state->getValue('china_mode'));
    $config->set('google_maps_base_url', $form_state->getValue('google_maps_base_url'));

    $parameters = $form_state->getValue('parameters');
    unset($parameters['libraries']['add']);
    $parameters['libraries'] = array_unique($parameters['libraries']);
    foreach ($parameters['libraries'] as $key => $library) {
      if (empty($library)) {
        unset($parameters['libraries'][$key]);
      }
    }
    $parameters['libraries'] = array_values($parameters['libraries']);
    $config->set('google_map_custom_url_parameters', $parameters);

    $config->save();

    // Confirmation on form submission.
    \Drupal::messenger()->addMessage($this->t('The configuration options have been saved.'));

    drupal_flush_all_caches();
  }

}
