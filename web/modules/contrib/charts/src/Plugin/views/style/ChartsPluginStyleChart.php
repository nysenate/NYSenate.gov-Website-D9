<?php

namespace Drupal\charts\Plugin\views\style;

use Drupal\charts\Services\ChartAttachmentServiceInterface;
use Drupal\charts\Plugin\chart\ChartManager;
use Drupal\charts\Services\ChartsSettingsService;
use Drupal\charts\Settings\ChartsBaseSettingsForm;
use Drupal\charts\Settings\ChartsTypeInfo;
use Drupal\charts\Theme\ChartsInterface;
use Drupal\core\form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Style plugin to render view as a chart.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "chart",
 *   title = @Translation("Chart"),
 *   help = @Translation("Render a chart of your data."),
 *   theme = "views_view_charts",
 *   display_types = { "normal" }
 * )
 */
class ChartsPluginStyleChart extends StylePluginBase implements ContainerFactoryPluginInterface {

  protected $usesFields = TRUE;

  protected $usesRowPlugin = TRUE;

  protected $attachmentService;

  /**
   * The chart manager service.
   *
   * @var \Drupal\charts\Plugin\chart\ChartManager
   */
  protected $chartManager;

  protected $moduleHandler;

  protected $chartsDefaultSettings;

  protected $chartsBaseSettingsForm;

  /**
   * Constructs a ChartsPluginStyleChart object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\charts\Services\ChartAttachmentServiceInterface $attachment_service
   *   The attachment service.
   * @param \Drupal\charts\Plugin\chart\ChartManager $chart_manager
   *   The chart manager service.
   * @param \Drupal\charts\Services\ChartsSettingsService $chartsSettings
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ChartAttachmentServiceInterface $attachment_service,
    ChartManager $chart_manager,
    ChartsSettingsService $chartsSettings
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->attachmentService = $attachment_service;
    $this->chartManager = $chart_manager;
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
      $container->get('charts.charts_attachment'),
      $container->get('plugin.manager.charts'),
      $container->get('charts.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['allow_advanced_rendering'] = [
      'default' => FALSE,
    ];

    // Get the default chart values.
    if ($defaults = $this->chartsDefaultSettings) {
      foreach ($defaults as $default_key => $default_value) {
        $options[$default_key]['default'] = $default_value;
      }
    }

    // @todo: ensure that chart extensions inherit defaults from parent
    // Remove the default setting for chart type so it can be inherited if this
    // is a chart extension type.
    if ($this->view->style_plugin === 'chart_extension') {
      $options['type']['default'] = NULL;
    }
    $options['path'] = ['default' => 'charts'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = ['#markup' => '<div class="error messages">' . t('You need at least one field before you can configure your table settings') . '</div>'];
    }

    // Limit grouping options (we only support one grouping field).
    if (isset($form['grouping'][0])) {
      $form['grouping'][0]['field']['#title'] = t('Grouping field');
      $form['grouping'][0]['field']['#description'] = t('If grouping by a particular field, that field will be used to determine stacking of the chart. Generally this will be the same field as what you select for the "Label field" below. If you do not have more than one "Provides data" field below, there will be nothing to stack. If you want to have another series displayed, use a "Chart attachment" display, and set it to attach to this display.');
      $form['grouping'][0]['field']['#attributes']['class'][] = 'charts-grouping-field';
      // Grouping by rendered version has no effect in charts. Hide the options.
      $form['grouping'][0]['rendered']['#access'] = FALSE;
      $form['grouping'][0]['rendered_strip']['#access'] = FALSE;
    }
    if (isset($form['grouping'][1])) {
      $form['grouping'][1]['#access'] = FALSE;
    }

    // Add a views-specific chart option to allow advanced rendering.
    $form['allow_advanced_rendering'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow advanced rendering'),
      '#description' => $this->t('Allow views field rewriting etc. for label and data fields. This can break charts if you rewrite the field to a value the charting library cannot handle - e.g. passing a string value into a numeric data column.'),
      '#default_value' => $this->options['allow_advanced_rendering'],
    ];

    // Merge in the global chart settings form.
    $field_options = $this->displayHandler->getFieldLabels();
    $form_state->set('default_options', $this->options);
    $form = $this->chartsBaseSettingsForm->getChartsBaseSettingsForm($form, 'view', $this->options, $field_options, ['style_options']);

  }

  /**
   * {@inheritdoc}
   */
  public function validate() {

    $errors = parent::validate();
    $dataFields = $this->options['data_fields'];
    $dataFieldsValueState = [];

    // Avoid calling validation before arriving on the view edit page.
    if (\Drupal::routeMatch()->getRouteName() != 'views_ui.add') {
      if (isset($dataFields)) {
        foreach ($dataFields as $value) {
          if (empty($value)) {
            array_push($dataFieldsValueState, 0);
          }
          else {
            array_push($dataFieldsValueState, 1);
          }
        }
      }

      // If total sum of dataFieldsValueState is less than 1, then no dataFields
      // were selected otherwise 1 or more selected total sum will be greater
      // than 1.
      if (array_sum($dataFieldsValueState) < 1) {
        $errors[] = $this->t('At least one data field must be selected in the chart configuration before this chart may be shown');
      }
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $field_handlers = $this->view->getHandlers('field');

    // Calculate the labels field alias.
    $label_field = FALSE;
    $label_field_key = NULL;
    if ($this->options['label_field'] && array_key_exists($this->options['label_field'], $field_handlers)) {
      $label_field = $field_handlers[$this->options['label_field']];
      $label_field_key = $this->options['label_field'];
    }

    // Assemble the fields to be used to provide data access.
    $data_field_options = array_filter($this->options['data_fields']);
    $data_fields = [];
    foreach ($data_field_options as $field_key) {
      if (isset($field_handlers[$field_key])) {
        $data_fields[$field_key] = $field_handlers[$field_key];
      }
    }
    // Do not allow the label field to be used as a data field.
    if (isset($data_fields[$label_field_key])) {
      unset($data_fields[$label_field_key]);
    }
    // Allow argument tokens in the title.
    if (!empty($this->view->build_info['substitutions'])) {
      $tokens = $this->view->build_info['substitutions'];
      $title = $this->options['title_position'] ? $this->options['title'] : FALSE;
      $title = $this->viewsTokenReplace($title, $tokens);
      $this->options['title'] = $title;
    }
    else {
      $title = $this->options['title_position'] ? $this->options['title'] : FALSE;
    }

    $chart_id = $this->view->id() . '__' . $this->view->current_display;
    $chart = [
      '#type' => 'chart',
      '#chart_type' => $this->options['type'],
      '#chart_library' => $this->options['library'],
      '#chart_id' => $chart_id,
      '#id' => ('chart_' . $chart_id),
    // '#title' => $this->options['title_position'] ? $this->options['title'] : FALSE,
      '#title' => $title,
      '#title_position' => $this->options['title_position'],
      '#tooltips' => $this->options['tooltips'],
      '#data_labels' => $this->options['data_labels'],
      '#colors' => isset($this->options['colors']) ? $this->options['colors'] : NULL,
      '#background' => $this->options['background'] ? $this->options['background'] : 'transparent',
      '#legend' => $this->options['legend_position'] ? TRUE : FALSE,
      '#legend_position' => $this->options['legend_position'] ? $this->options['legend_position'] : NULL,
      '#width' => $this->options['width'],
      '#height' => $this->options['height'],
      '#width_units' => $this->options['width_units'],
      '#height_units' => $this->options['height_units'],
      '#view' => $this->view,
      // Pass info about the actual view results to allow further processing.
      '#theme' => 'views_view_charts',
    ];
    $chartTypes = new ChartsTypeInfo();
    $chart_type_info = $chartTypes->getChartType($this->options['type']);
    if ($chart_type_info['axis'] === ChartsInterface::CHARTS_SINGLE_AXIS) {
      $data_field_key = key($data_fields);
      $data_field = $data_fields[$data_field_key];
      $data = [];
      $this->renderFields($this->view->result);
      $renders = $this->rendered_fields;
      foreach ($renders as $row_number => $row) {
        $data_row = [];
        if ($label_field_key) {
          // Labels need to be decoded, as the charting library will re-encode.
          $data_row[] = htmlspecialchars_decode($this->getField($row_number, $label_field_key), ENT_QUOTES);
        }
        $value = $this->getField($row_number, $data_field_key);
        // Convert empty strings to NULL.
        if ($value === '') {
          $value = NULL;
        }
        else {
          // Strip thousands placeholders if present, then cast to float.
          $value = (float) str_replace([',', ' '], '', $value);
        }
        $data_row[] = $value;
        $data[] = $data_row;
      }

      if ($label_field) {
        $chart['#legend_title'] = $label_field['label'];
      }

      $chart[$this->view->current_display . '_series'] = [
        '#type' => 'chart_data',
        '#data' => $data,
        '#title' => $data_field['label'],
      ];

    }
    else {
      $chart['xaxis'] = [
        '#type' => 'chart_xaxis',
        '#title' => $this->options['xaxis_title'] ? $this->options['xaxis_title'] : FALSE,
        '#labels_rotation' => $this->options['xaxis_labels_rotation'],
      ];
      $chart['yaxis'] = [
        '#type' => 'chart_yaxis',
        '#title' => $this->options['yaxis_title'] ? $this->options['yaxis_title'] : FALSE,
        '#labels_rotation' => $this->options['yaxis_labels_rotation'],
        '#max' => $this->options['yaxis_max'],
        '#min' => $this->options['yaxis_min'],
      ];
      $sets = $this->renderGrouping($this->view->result, $this->options['grouping'], TRUE);
      $series_index = -1;
      foreach ($sets as $series_label => $data_set) {
        $series_index++;
        foreach ($data_fields as $field_key => $field_handler) {
          $chart[$this->view->current_display . '__' . $field_key . '_' . $series_index] = [
            '#type' => 'chart_data',
            '#data' => [],
            // If using a grouping field, inherit from the chart level colors.
            '#color' => ($series_label === '' && isset($this->options['field_colors'][$field_key])) ? $this->options['field_colors'][$field_key] : NULL,
            '#title' => $series_label ? $series_label : $field_handler['label'],
            '#prefix' => $this->options['yaxis_prefix'] ? $this->options['yaxis_prefix'] : NULL,
            '#suffix' => $this->options['yaxis_suffix'] ? $this->options['yaxis_suffix'] : NULL,
            '#decimal_count' => $this->options['yaxis_decimal_count'] ? $this->options['yaxis_decimal_count'] : NULL,
          ];
        }

        // Grouped results come back indexed by their original result number
        // from before the grouping, so we need to keep our own row number when
        // looping through the rows.
        $row_number = 0;
        foreach ($data_set['rows'] as $result_number => $row) {
          if ($label_field_key && !isset($chart['xaxis']['#labels'][$row_number])) {
            $chart['xaxis']['#labels'][$row_number] = $this->getField($result_number, $label_field_key);
          }
          foreach ($data_fields as $field_key => $field_handler) {
            // Don't allow the grouping field to provide data.
            if (isset($this->options['grouping'][0]['field']) && $field_key === $this->options['grouping'][0]['field']) {
              continue;
            }
            $value = $this->getField($result_number, $field_key);
            // Convert empty strings to NULL.
            if ($value === '') {
              $value = NULL;
            }
            else {
              // Strip thousands placeholders if present, then cast to float.
              $value = (float) str_replace([',', ' '], '', $value);
            }
            $chart[$this->view->current_display . '__' . $field_key . '_' . $series_index]['#data'][] = $value;
          }
          $row_number++;
        }
      }
    }

    // Check if this display has any children charts that should be applied
    // on top of it.
    $children_displays = $this->getChildrenChartDisplays();
    // Contains the different subviews of the attachments.
    $attachments = [];

    foreach ($children_displays as $child_display) {
      // If the user doesn't have access to the child display, skip.
      if (!$this->view->access($child_display)) {
        continue;
      }

      // Generate the subchart by executing the child display. We load a fresh
      // view here to avoid collisions in shifting the current display while in
      // a display.
      $subview = $this->view->createDuplicate();
      $subview->setDisplay($child_display);

      // Copy the settings for our axes over to the child view.
      foreach ($this->options as $option_name => $option_value) {
        if ($this->view->displayHandlers->get($child_display)->options['inherit_yaxis'] === '1') {
          $subview->display_handler->options['style_options'][$option_name] = $option_value;
        }
      }

      // Set the arguments on the subview if it is configured to inherit arguments.
      if (!empty($this->view->displayHandlers->get($child_display)->display['display_options']['inherit_arguments']) && $this->view->displayHandlers->get($child_display)->display['display_options']['inherit_arguments'] == '1') {
        $subview->setArguments($this->view->args);
      }

      // Execute the subview and get the result.
      $subview->preExecute();
      $subview->execute();

      // If there's no results, don't attach the subview.
      if (empty($subview->result)) {
        continue;
      }

      $subchart = $subview->style_plugin->render();
      // Add attachment views to attachments array.
      array_push($attachments, $subview);

      // Create a secondary axis if needed.
      if ($this->view->displayHandlers->get($child_display)->options['inherit_yaxis'] !== '1' && isset($subchart['yaxis'])) {
        $chart['secondary_yaxis'] = $subchart['yaxis'];
        $chart['secondary_yaxis']['#opposite'] = TRUE;
      }

      // Merge in the child chart data.
      foreach (Element::children($subchart) as $key) {
        if ($subchart[$key]['#type'] === 'chart_data') {
          $chart[$key] = $subchart[$key];
          // If the subchart is a different type than the parent chart, set
          // the #chart_type property on the individual chart data elements.
          if ($subchart['#chart_type'] !== $chart['#chart_type']) {
            $chart[$key]['#chart_type'] = $subchart['#chart_type'];
          }
          if ($this->view->displayHandlers->get($child_display)->options['inherit_yaxis'] !== '1') {
            $chart[$key]['#target_axis'] = 'secondary_yaxis';
          }
        }
      }
    }
    $this->attachmentService->setAttachmentViews($attachments);

    // Print the chart.
    return $chart;
  }

  /**
   * Utility function to check if this chart has a parent display.
   *
   * @return bool
   *   Parent Display.
   */
  public function getParentChartDisplay() {
    $parent_display = FALSE;

    return $parent_display;
  }

  /**
   * Utility function to check if this chart has children displays.
   *
   * @return array
   *   Children Chart Display.
   */
  public function getChildrenChartDisplays() {

    $children_displays = $this->displayHandler->getAttachedDisplays();
    foreach ($children_displays as $key => $child) {
      $display_handler = $this->view->displayHandlers->get($child);
      // Unset disabled & non chart attachments.
      if ((!$display_handler->isEnabled()) || (strstr($child, 'chart_extension') == !TRUE)) {
        unset($children_displays[$key]);
      }
    }
    $children_displays = array_values($children_displays);

    return $children_displays;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    if (!empty($this->options['library'])) {
      $plugin_definition = $this->chartManager->getDefinition($this->options['library']);
      $dependencies['module'] = [$plugin_definition['provider']];
    }

    return $dependencies;
  }

}
