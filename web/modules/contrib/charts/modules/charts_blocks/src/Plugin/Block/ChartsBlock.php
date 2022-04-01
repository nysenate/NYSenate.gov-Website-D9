<?php

namespace Drupal\charts_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\charts\Settings\ChartsDefaultSettings;
use Drupal\charts\Services\ChartsSettingsService;
use Drupal\charts\Settings\ChartsBaseSettingsForm;
use Drupal\charts\Settings\ChartsDefaultColors;

/**
 * Provides a 'ChartsBlock' block.
 *
 * @Block(
 *  id = "charts_block",
 *  admin_label = @Translation("Charts block"),
 * )
 */
class ChartsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Chart defaults.
   *
   * @var mixed
   */
  protected $defaults;

  /**
   * Colors.
   *
   * @var mixed
   */
  protected $colors;

  /**
   * Chart default settings.
   *
   * @var mixed
   */
  protected $chartsDefaultSettings;

  /**
   * Chart base settings form.
   *
   * @var mixed
   */
  protected $chartsBaseSettingsForm;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ChartsSettingsService $chartsSettings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->defaults = new ChartsDefaultSettings();
    $this->colors = new ChartsDefaultColors();
    $this->chartsDefaultSettings = $chartsSettings->getChartsSettings();
    $this->chartsBaseSettingsForm = new ChartsBaseSettingsForm();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('charts.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);

    if (!empty($this->chartsDefaultSettings)) {
      // Get the charts default settings.
      $default_options = $this->chartsDefaultSettings;
      // Merge the charts default settings with this block's configuration.
      $defaults = array_merge($default_options, $this->configuration);
    }
    else {
      $defaults = $this->configuration;
    }
    $form = $this->chartsBaseSettingsForm->getChartsBaseSettingsForm($form, 'block', $defaults, [], []);

    /*
     * @todo figure out why the block label field does not respect weight.
     */
    // Reposition the block form fields to the top.
    $form['label']['#weight'] = '-26';
    $form['label_display']['#weight'] = '-25';

    // Check if chart will be a series.
    $form['series'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Series'),
      '#description' => $this->t('Does this chart graph more than a single series?'),
      '#default_value' => !empty($this->configuration['series']) ? $this->configuration['series'] : 0,
      '#weight' => '-22',
    ];

    // If a single series.
    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data (single series)'),
      '#description' => $this->t('Enter the data for your chart, separated by comma: 1,3,5,7'),
      '#default_value' => !empty($this->configuration['data']) ? $this->configuration['data'] : '',
      '#weight' => '-19',
      '#states' => [
        'invisible' => [
          ':input[name="settings[series]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['series_label'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Series label (single series)'),
      '#description' => $this->t('Provide a label for your legend'),
      '#default_value' => !empty($this->configuration['series_label']) ? $this->configuration['series_label'] : '',
      '#weight' => '-18',
      '#states' => [
        'invisible' => [
          ':input[name="settings[series]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['color'] = [
      '#title' => $this->t('Color (single series)'),
      '#type' => 'markup',
      '#theme_wrappers' => ['form_element'],
      '#prefix' => '<div class="chart-colors">',
      '#suffix' => '</div>',
      '#weight' => '-17',
      '#states' => [
        'invisible' => [
          ':input[name="settings[series]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['color'][0] = [
      '#type' => 'textfield',
      '#attributes' => ['TYPE' => 'color'],
      '#size' => 10,
      '#maxlength' => 7,
      '#theme_wrappers' => [],
      '#suffix' => ' ',
      '#default_value' => !empty($this->configuration['color']) ? $this->configuration['color'] : '#000000',
      '#states' => [
        'invisible' => [
          ':input[name="settings[series]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // If making a series chart, the API requires this format.
    $form['data_series'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data (multiple series)'),
      '#description' => $this->t('Enter the data for your chart using this format (must be valid JSON): {"name":"Number of players","color":"#0d233a","data":[50,60,100,132,133,234]},{"name":"Number of coaches","color":"#ff0000","data":[50,80,100,32,133,234]}'),
      '#default_value' => !empty($this->configuration['data_series']) ? $this->configuration['data_series'] : '',
      '#weight' => '-17',
      '#states' => [
        'invisible' => [
          ':input[name="settings[series]"]' => ['checked' => FALSE],
        ],
      ],
      '#placeholder' => 'Check the instructions below for formatting syntax.',
    ];
    $form['categories'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('List categories. You should have as many as you have points of data in a series. They should be comma-separated: One,Two,Three,Four'),
      '#default_value' => !empty($this->configuration['categories']) ? $this->configuration['categories'] : '',
      '#weight' => '-16',
    ];
    // Enable stacking.
    unset($form['grouping']['#parents']);
    $form['grouping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stacking'),
      '#description' => $this->t('Enable stacking'),
      '#default_value' => !empty($this->configuration['grouping']) ? $this->configuration['grouping'] : 0,
      '#weight' => '-15',
    ];

    /*
     * Unset the #parents element from default form, then set the
     * default value.
     */
    unset($form['library']['#parents']);
    $form['library']['#default_value'] = !empty($this->configuration['library']) ? $this->configuration['library'] : $defaults['library'];
    $form['library']['#weight'] = '-23';

    unset($form['type']['#parents']);
    $form['type']['#default_value'] = !empty($this->configuration['type']) ? $this->configuration['type'] : $defaults['type'];
    $form['type']['#weight'] = '-24';

    unset($form['display']['title']['#parents']);
    $form['display']['title']['#default_value'] = !empty($this->configuration['title']) ? $this->configuration['title'] : '';

    unset($form['display']['title_position']['#parents']);
    $form['display']['title_position']['#default_value'] = !empty($this->configuration['title_position']) ? $this->configuration['title_position'] : $defaults['title_position'];

    unset($form['display']['data_labels']['#parents']);
    $form['display']['data_labels']['#default_value'] = !empty($this->configuration['data_labels']) ? $this->configuration['data_labels'] : $defaults['data_labels'];

    unset($form['display']['data_markers']['#parents']);
    $form['display']['data_markers']['#default_value'] = !empty($this->configuration['data_markers']) ? $this->configuration['data_markers'] : $defaults['data_markers'];

    unset($form['display']['background']['#parents']);
    $form['display']['background']['#default_value'] = !empty($this->configuration['background']) ? $this->configuration['background'] : $defaults['background'];

    unset($form['display']['three_dimensional']['#parents']);
    $form['display']['three_dimensional']['#default_value'] = !empty($this->configuration['three_dimensional']) ? $this->configuration['three_dimensional'] : $defaults['three_dimensional'];

    unset($form['display']['polar']['#parents']);
    $form['display']['polar']['#default_value'] = !empty($this->configuration['polar']) ? $this->configuration['polar'] : $defaults['polar'];

    unset($form['display']['legend_position']['#parents']);
    $form['display']['legend_position']['#default_value'] = !empty($this->configuration['legend_position']) ? $this->configuration['legend_position'] : $defaults['legend_position'];

    unset($form['display']['tooltips']['#parents']);
    $form['display']['tooltips']['#default_value'] = !empty($this->configuration['tooltips']) ? $this->configuration['tooltips'] : $defaults['tooltips'];

    unset($form['display']['dimensions']['height']['#parents']);
    $form['display']['dimensions']['height']['#default_value'] = !empty($this->configuration['height']) ? $this->configuration['height'] : $defaults['height'];

    unset($form['display']['dimensions']['width']['#parents']);
    $form['display']['dimensions']['width']['#default_value'] = !empty($this->configuration['width']) ? $this->configuration['width'] : $defaults['width'];

    unset($form['display']['dimensions']['height_units']['#parents']);
    $form['display']['dimensions']['height_units']['#default_value'] = !empty($this->configuration['height_units']) ? $this->configuration['height_units'] : $defaults['height_units'];

    unset($form['display']['dimensions']['width_units']['#parents']);
    $form['display']['dimensions']['width_units']['#default_value'] = !empty($this->configuration['width_units']) ? $this->configuration['width_units'] : $defaults['width_units'];

    unset($form['display']['gauge']['green_to']['#parents']);
    $form['display']['gauge']['green_to']['#default_value'] = !empty($this->configuration['green_to']) ? $this->configuration['green_to'] : $defaults['green_to'];

    unset($form['display']['gauge']['green_from']['#parents']);
    $form['display']['gauge']['green_from']['#default_value'] = !empty($this->configuration['green_from']) ? $this->configuration['green_from'] : $defaults['green_from'];

    unset($form['display']['gauge']['yellow_to']['#parents']);
    $form['display']['gauge']['yellow_to']['#default_value'] = !empty($this->configuration['yellow_to']) ? $this->configuration['yellow_to'] : $defaults['yellow_to'];

    unset($form['display']['gauge']['yellow_from']['#parents']);
    $form['display']['gauge']['yellow_from']['#default_value'] = !empty($this->configuration['yellow_from']) ? $this->configuration['yellow_from'] : $defaults['yellow_from'];

    unset($form['display']['gauge']['red_to']['#parents']);
    $form['display']['gauge']['red_to']['#default_value'] = !empty($this->configuration['red_to']) ? $this->configuration['red_to'] : $defaults['red_to'];

    unset($form['display']['gauge']['red_from']['#parents']);
    $form['display']['gauge']['red_from']['#default_value'] = !empty($this->configuration['red_from']) ? $this->configuration['red_from'] : $defaults['red_from'];

    unset($form['display']['gauge']['max']['#parents']);
    $form['display']['gauge']['max']['#default_value'] = !empty($this->configuration['max']) ? $this->configuration['max'] : $defaults['max'];

    unset($form['display']['gauge']['min']['#parents']);
    $form['display']['gauge']['min']['#default_value'] = !empty($this->configuration['min']) ? $this->configuration['min'] : $defaults['min'];

    unset($form['xaxis']['xaxis_title']['#parents']);
    $form['xaxis']['xaxis_title']['#default_value'] = !empty($this->configuration['xaxis_title']) ? $this->configuration['xaxis_title'] : $defaults['xaxis_title'];

    unset($form['xaxis']['xaxis_labels_rotation']['#parents']);
    $form['xaxis']['xaxis_labels_rotation']['#default_value'] = !empty($this->configuration['xaxis_labels_rotation']) ? $this->configuration['xaxis_labels_rotation'] : $defaults['xaxis_labels_rotation'];

    unset($form['yaxis']['title']['#parents']);
    $form['yaxis']['title']['#default_value'] = !empty($this->configuration['yaxis_title']) ? $this->configuration['yaxis_title'] : $defaults['yaxis_title'];

    unset($form['yaxis']['yaxis_min']['#parents']);
    $form['yaxis']['yaxis_min']['#default_value'] = !empty($this->configuration['yaxis_min']) ? $this->configuration['yaxis_min'] : $defaults['yaxis_min'];

    unset($form['yaxis']['yaxis_max']['#parents']);
    $form['yaxis']['yaxis_max']['#default_value'] = !empty($this->configuration['yaxis_max']) ? $this->configuration['yaxis_max'] : $defaults['yaxis_max'];

    unset($form['yaxis']['yaxis_prefix']['#parents']);
    $form['yaxis']['yaxis_prefix']['#default_value'] = !empty($this->configuration['yaxis_prefix']) ? $this->configuration['yaxis_prefix'] : $defaults['yaxis_prefix'];

    unset($form['yaxis']['yaxis_suffix']['#parents']);
    $form['yaxis']['yaxis_suffix']['#default_value'] = !empty($this->configuration['yaxis_suffix']) ? $this->configuration['yaxis_suffix'] : $defaults['yaxis_suffix'];

    unset($form['yaxis']['yaxis_decimal_count']['#parents']);
    $form['yaxis']['yaxis_decimal_count']['#default_value'] = !empty($this->configuration['yaxis_decimal_count']) ? $this->configuration['yaxis_decimal_count'] : $defaults['yaxis_decimal_count'];

    unset($form['yaxis']['yaxis_labels_rotation']['#parents']);
    $form['yaxis']['yaxis_labels_rotation']['#default_value'] = !empty($this->configuration['yaxis_labels_rotation']) ? $this->configuration['yaxis_labels_rotation'] : $defaults['yaxis_labels_rotation'];

    $form['yaxis']['inherit_yaxis']['#default_value'] = !empty($this->configuration['inherit_yaxis']) ? $this->configuration['inherit_yaxis'] : '';

    // There are no parents for the secondary y axis.
    $form['yaxis']['secondary_yaxis']['title']['#default_value'] = !empty($this->configuration['secondary_yaxis_title']) ? $this->configuration['secondary_yaxis_title'] : $defaults['yaxis_title'];
    $form['yaxis']['secondary_yaxis']['minmax']['min']['#default_value'] = !empty($this->configuration['secondary_yaxis_min']) ? $this->configuration['secondary_yaxis_min'] : $defaults['yaxis_min'];
    $form['yaxis']['secondary_yaxis']['minmax']['max']['#default_value'] = !empty($this->configuration['secondary_yaxis_max']) ? $this->configuration['secondary_yaxis_max'] : $defaults['yaxis_max'];
    $form['yaxis']['secondary_yaxis']['prefix']['#default_value'] = !empty($this->configuration['secondary_yaxis_prefix']) ? $this->configuration['secondary_yaxis_prefix'] : $defaults['yaxis_prefix'];
    $form['yaxis']['secondary_yaxis']['suffix']['#default_value'] = !empty($this->configuration['secondary_yaxis_suffix']) ? $this->configuration['secondary_yaxis_suffix'] : $defaults['yaxis_suffix'];
    $form['yaxis']['secondary_yaxis']['decimal_count']['#default_value'] = !empty($this->configuration['secondary_yaxis_decimal_count']) ? $this->configuration['secondary_yaxis_decimal_count'] : $defaults['yaxis_decimal_count'];
    $form['yaxis']['secondary_yaxis']['labels_rotation']['#default_value'] = !empty($this->configuration['secondary_yaxis_labels_rotation']) ? $this->configuration['secondary_yaxis_labels_rotation'] : $defaults['yaxis_labels_rotation'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['library'] = $form_state->getValue('library');
    $this->configuration['type'] = $form_state->getValue('type');
    $this->configuration['series'] = $form_state->getValue('series');
    $this->configuration['data'] = $form_state->getValue('data');
    $this->configuration['color'] = $form_state->getValue('color');
    $this->configuration['data_series'] = $form_state->getValue('data_series');
    $this->configuration['series_label'] = $form_state->getValue('series_label');
    $this->configuration['categories'] = $form_state->getValue('categories');
    $this->configuration['grouping'] = $form_state->getValue('grouping');
    $this->configuration['field_colors'] = $form_state->getValue('field_colors');
    $this->configuration['title'] = $form_state->getValue(['display', 'title']);
    $this->configuration['title_position'] = $form_state->getValue([
      'display',
      'title_position',
    ]);
    $this->configuration['data_labels'] = $form_state->getValue([
      'display',
      'data_labels',
    ]);
    $this->configuration['data_markers'] = $form_state->getValue([
      'display',
      'data_markers',
    ]);
    $this->configuration['legend'] = $form_state->getValue('legend');
    $this->configuration['legend_position'] = $form_state->getValue([
      'display',
      'legend_position',
    ]);
    $this->configuration['background'] = $form_state->getValue([
      'display',
      'background',
    ]);
    $this->configuration['three_dimensional'] = $form_state->getValue([
      'display',
      'three_dimensional',
    ]);
    $this->configuration['polar'] = $form_state->getValue(['display', 'polar']);
    $this->configuration['tooltips'] = $form_state->getValue([
      'display',
      'tooltips',
    ]);
    $this->configuration['tooltips_use_html'] = $form_state->getValue('tooltips_use_html');
    $this->configuration['width'] = $form_state->getValue([
      'display',
      'dimensions',
      'width',
    ]);
    $this->configuration['height'] = $form_state->getValue([
      'display',
      'dimensions',
      'height',
    ]);
    $this->configuration['width_units'] = $form_state->getValue([
      'display',
      'dimensions',
      'width_units',
    ]);
    $this->configuration['height_units'] = $form_state->getValue([
      'display',
      'dimensions',
      'height_units',
    ]);
    $this->configuration['xaxis_title'] = $form_state->getValue([
      'xaxis',
      'xaxis_title',
    ]);
    $this->configuration['xaxis_labels_rotation'] = $form_state->getValue([
      'xaxis',
      'xaxis_labels_rotation',
    ]);
    $this->configuration['yaxis_title'] = $form_state->getValue([
      'yaxis',
      'title',
    ]);
    $this->configuration['yaxis_min'] = $form_state->getValue([
      'yaxis',
      'yaxis_min',
    ]);
    $this->configuration['yaxis_max'] = $form_state->getValue([
      'yaxis',
      'yaxis_max',
    ]);
    $this->configuration['yaxis_prefix'] = $form_state->getValue([
      'yaxis',
      'yaxis_prefix',
    ]);
    $this->configuration['yaxis_suffix'] = $form_state->getValue([
      'yaxis',
      'yaxis_suffix',
    ]);
    $this->configuration['yaxis_decimal_count'] = $form_state->getValue([
      'yaxis',
      'yaxis_decimal_count',
    ]);
    $this->configuration['yaxis_labels_rotation'] = $form_state->getValue([
      'yaxis',
      'yaxis_labels_rotation',
    ]);
    $this->configuration['inherit_yaxis'] = $form_state->getValue([
      'yaxis',
      'inherit_yaxis',
    ]);
    $this->configuration['secondary_yaxis_title'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'title',
    ]);
    $this->configuration['secondary_yaxis_min'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'minmax',
      'min',
    ]);
    $this->configuration['secondary_yaxis_max'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'minmax',
      'max',
    ]);
    $this->configuration['secondary_yaxis_prefix'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'yaxis_prefix',
    ]);
    $this->configuration['secondary_yaxis_suffix'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'yaxis_suffix',
    ]);
    $this->configuration['secondary_yaxis_decimal_count'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'yaxis_decimal_count',
    ]);
    $this->configuration['secondary_yaxis_labels_rotation'] = $form_state->getValue([
      'yaxis',
      'secondary_yaxis',
      'yaxis_labels_rotation',
    ]);

    // Set gauge specific settings.
    $gauge_fields = [
      'green_from',
      'green_to',
      'red_from',
      'red_to',
      'yellow_from',
      'yellow_to',
      'max',
      'min',
    ];
    foreach ($gauge_fields as $field) {
      $this->configuration[$field] = $form_state->getValue([
        'display',
        'gauge',
        $field,
      ]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $categories = explode(",", $this->configuration['categories']);
    $data = json_decode('[' . $this->configuration['data'] . ']', TRUE);

    if (!empty($this->configuration['data_series'])) {
      $seriesData = json_decode('[' . $this->configuration['data_series'] . ']', TRUE);
      foreach ($seriesData as &$data) {
        if (empty($data['type'])) {
          $data['type'] = $this->configuration['type'];
        }
      }
    }
    else {
      $seriesData = [
        [
          'name' => $this->configuration['series_label'],
          'color' => $this->configuration['color'][0],
          'type' => $this->configuration['type'],
          'data' => $data,
        ],
      ];
    }

    /*
     * Helps for pie and donut charts, which need more colors than configurable
     * for a single series.
     */
    $colors = $this->colors->getDefaultColors();

    $options = [
      'library' => $this->configuration['library'],
      'type' => $this->configuration['type'],
      'grouping' => $this->configuration['grouping'],
      'field_colors' => $this->configuration['field_colors'],
      'colors' => $colors,
      'title' => $this->configuration['title'],
      'title_position' => $this->configuration['title_position'],
      'data_labels' => $this->configuration['data_labels'],
      'data_markers' => $this->configuration['data_markers'],
      'legend' => $this->configuration['legend'],
      'legend_position' => $this->configuration['legend_position'],
      'background' => $this->configuration['background'],
      'three_dimensional' => $this->configuration['three_dimensional'],
      'polar' => $this->configuration['polar'],
      'tooltips' => $this->configuration['tooltips'],
      'tooltips_use_html' => $this->configuration['tooltips_use_html'],
      'width' => $this->configuration['width'],
      'height' => $this->configuration['height'],
      'width_units' => $this->configuration['width_units'],
      'height_units' => $this->configuration['height_units'],
      'xaxis_title' => $this->configuration['xaxis_title'],
      'xaxis_labels_rotation' => $this->configuration['xaxis_labels_rotation'],
      'yaxis_title' => $this->configuration['yaxis_title'],
      'yaxis_min' => $this->configuration['yaxis_min'],
      'yaxis_max' => $this->configuration['yaxis_max'],
      'yaxis_prefix' => $this->configuration['yaxis_prefix'],
      'yaxis_suffix' => $this->configuration['yaxis_suffix'],
      'yaxis_decimal_count' => $this->configuration['yaxis_decimal_count'],
      'yaxis_labels_rotation' => $this->configuration['yaxis_labels_rotation'],
      'inherit_yaxis' => $this->configuration['inherit_yaxis'],
      'secondary_yaxis_title' => $this->configuration['secondary_yaxis_title'],
      'secondary_yaxis_min' => $this->configuration['secondary_yaxis_min'],
      'secondary_yaxis_max' => $this->configuration['secondary_yaxis_max'],
      'secondary_yaxis_prefix' => $this->configuration['secondary_yaxis_prefix'],
      'secondary_yaxis_suffix' => $this->configuration['secondary_yaxis_suffix'],
      'secondary_yaxis_decimal_count' => $this->configuration['secondary_yaxis_decimal_count'],
      'secondary_yaxis_labels_rotation' => $this->configuration['secondary_yaxis_labels_rotation'],
    ];

    // Set gauge specific fields.
    $gauge_fields = [
      'green_from',
      'green_to',
      'red_from',
      'red_to',
      'yellow_from',
      'yellow_to',
      'max',
      'min',
    ];
    foreach ($gauge_fields as $field) {
      $options[$field] = $this->configuration[$field];
    }

    // Adjustments added to provide the secondary y-axis features from Views.
    $secondaryOptions = [];
    if ($options['inherit_yaxis'] == 1) {
      $secondaryOptions[0]['inherit_yaxis'] = 0;
      $secondaryOptions[0]['style']['options']['yaxis_title'] = $options['secondary_yaxis_title'];
      $secondaryOptions[0]['style']['options']['yaxis_min'] = $options['secondary_yaxis_min'];
      $secondaryOptions[0]['style']['options']['yaxis_max'] = $options['secondary_yaxis_max'];
      $secondaryOptions[0]['style']['options']['yaxis_prefix'] = $options['secondary_yaxis_prefix'];
      $secondaryOptions[0]['style']['options']['yaxis_suffix'] = $options['secondary_yaxis_suffix'];
      $secondaryOptions[0]['style']['options']['yaxis_decimal_count'] = $options['secondary_yaxis_decimal_count'];
      $secondaryOptions[0]['style']['options']['yaxis_labels_rotation'] = $options['secondary_yaxis_labels_rotation'];
      $secondaryOptions[0]['type'] = 'chart';
    }

    // Creates a UUID for the chart ID.
    $uuid_service = \Drupal::service('uuid');
    $chartId = 'chart-' . $uuid_service->generate();

    $build = [
      '#theme' => 'charts_blocks',
      '#library' => $this->configuration['library'],
      '#categories' => $categories,
      '#seriesData' => $seriesData,
      '#secondaryOptions' => $secondaryOptions,
      '#options' => $options,
      '#id' => $chartId,
      '#override' => [],
    ];

    return $build;
  }

}
