<?php

namespace Drupal\geolocation_google_maps\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of 'geolocation_geometry_widget_google_maps' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_geometry_widget_google_maps",
 *   label = @Translation("Geolocation Geometry Google Maps API - GeoJSON"),
 *   field_types = {
 *     "geolocation_geometry_point",
 *     "geolocation_geometry_multi_point",
 *     "geolocation_geometry_linestring",
 *     "geolocation_geometry_multi_linestring",
 *     "geolocation_geometry_polygon",
 *     "geolocation_geometry_multi_polygon",
 *     "geolocation_geometry_geometry",
 *     "geolocation_geometry_multi_geometry"
 *   }
 * )
 */
class GeolocationGeometryWidgetGoogleMaps extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected $mapProviderId = 'google_maps';

  /**
   * {@inheritdoc}
   */
  protected $mapProviderSettingsFormId = 'google_map_settings';

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Map Provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface
   */
  protected $mapProvider = NULL;

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    $settings = parent::getSettings();
    $map_settings = [];
    if (!empty($settings[$this->mapProviderSettingsFormId])) {
      $map_settings = $settings[$this->mapProviderSettingsFormId];
    }

    if (!empty($this->mapProviderId)) {
      $this->mapProvider = \Drupal::service('plugin.manager.geolocation.mapprovider')->getMapProvider($this->mapProviderId);
    }

    $settings = NestedArray::mergeDeep(
      $settings,
      [
        $this->mapProviderSettingsFormId => $this->mapProvider->getSettings($map_settings),
      ]
    );

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $element = [];

    if ($this->mapProvider) {
      $element[$this->mapProviderSettingsFormId] = $this->mapProvider->getSettingsForm(
        $settings[$this->mapProviderSettingsFormId],
        [
          'fields',
          $this->fieldDefinition->getName(),
          'settings_edit_form',
          'settings',
          $this->mapProviderSettingsFormId,
        ]
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $map_provider_settings = empty($settings[$this->mapProviderSettingsFormId]) ? [] : $settings[$this->mapProviderSettingsFormId];

    $summary = array_replace_recursive($summary, $this->mapProvider->getSettingsSummary($map_provider_settings));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['#type'] = 'container';
    $element['#attached'] = [
      'library' => [
        'geolocation_google_maps/widget.google_maps.geojson',
      ],
    ];
    $element['#attributes'] = [
      'class' => [
        'geolocation-geometry-widget-google-maps-geojson',
      ],
    ];

    $element['geojson'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GeoJSON'),
      '#default_value' => isset($items[$delta]->geojson) ? $items[$delta]->geojson : NULL,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => [
          'geolocation-geometry-widget-google-maps-geojson-input',
        ],
      ],
    ];

    $settings = $this->getSettings();

    $element['map'] = [
      '#type' => 'geolocation_map',
      '#maptype' => 'google_maps',
      '#weight' => -10,
      '#settings' => $settings[$this->mapProviderSettingsFormId],
      '#context' => ['widget' => $this],
      '#attributes' => [
        'class' => [
          'geolocation-geometry-widget-google-maps-geojson-map',
        ],
      ],
    ];

    return $element;
  }

}
