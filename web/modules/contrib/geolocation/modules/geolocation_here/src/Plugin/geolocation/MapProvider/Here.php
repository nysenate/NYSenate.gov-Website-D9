<?php

namespace Drupal\geolocation_here\Plugin\geolocation\MapProvider;

use Drupal\geolocation\MapProviderBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides HERE Maps API.
 *
 * @MapProvider(
 *   id = "here",
 *   name = @Translation("HERE Maps"),
 *   description = @Translation("Here support."),
 * )
 */
class Here extends MapProviderBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    return array_replace_recursive(
      parent::getDefaultSettings(),
      [
        'zoom' => 10,
        'height' => '400px',
        'width' => '100%',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings) {
    $settings = parent::getSettings($settings);

    $settings['zoom'] = (int) $settings['zoom'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $settings) {
    $settings = array_replace_recursive(
      self::getDefaultSettings(),
      $settings
    );
    $summary = parent::getSettingsSummary($settings);
    $summary[] = $this->t('Zoom level: @zoom', ['@zoom' => $settings['zoom']]);
    $summary[] = $this->t('Height: @height', ['@height' => $settings['height']]);
    $summary[] = $this->t('Width: @width', ['@width' => $settings['width']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = []) {
    $settings += self::getDefaultSettings();
    if ($parents) {
      $parents_string = implode('][', $parents);
    }
    else {
      $parents_string = NULL;
    }

    $form = parent::getSettingsForm($settings, $parents);

    $form['height'] = [
      '#group' => $parents_string,
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['height'],
    ];
    $form['width'] = [
      '#group' => $parents_string,
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['width'],
    ];
    $form['zoom'] = [
      '#group' => $parents_string,
      '#type' => 'select',
      '#title' => $this->t('Zoom level'),
      '#options' => range(0, 20),
      '#description' => $this->t('The initial resolution at which to display the map, where zoom 0 corresponds to a map of the Earth fully zoomed out, and higher zoom levels zoom in at a higher resolution.'),
      '#default_value' => $settings['zoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array $render_array, array $map_settings, array $context = []) {
    $config = \Drupal::config('here_maps.settings');

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      empty($render_array['#attached']) ? [] : $render_array['#attached'],
      [
        'library' => [
          'geolocation_here/geolocation.here',
        ],
        'drupalSettings' => [
          'geolocation' => [
            'hereMapsAppId' => $config->get('app_id'),
            'hereMapsAppCode' => $config->get('app_code'),
            'maps' => [
              $render_array['#id'] => [
                'settings' => [
                  'here_settings' => $map_settings,
                ],
              ],
            ],
          ],
        ],
      ]
    );

    return parent::alterRenderArray($render_array, $map_settings, $context);
  }

  /**
   * {@inheritdoc}
   */
  public static function getControlPositions() {
    return [
      'topleft' => t('Top left'),
      'topright' => t('Top right'),
      'bottomleft' => t('Bottom left'),
      'bottomright' => t('Bottom right'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterCommonMap(array $render_array, array $map_settings, array $context) {
    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      empty($render_array['#attached']) ? [] : $render_array['#attached'],
      [
        'library' => [
          'geolocation_here/commonmap.here',
        ],
      ]
    );

    return $render_array;
  }

}
