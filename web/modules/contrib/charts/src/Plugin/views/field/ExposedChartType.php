<?php

namespace Drupal\charts\Plugin\views\field;

use Drupal\charts\Settings\ChartsTypeInfo;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Views handler that exposes a Chart Type field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("field_exposed_chart_type")
 */
class ExposedChartType extends FieldPluginBase {

  /**
   * @var ChartsTypeInfo
   */
  private $chartsTypes;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->chartsTypes = new ChartsTypeInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExposed() {
    return TRUE;
  }

  public function buildExposedForm(&$form, FormStateInterface $form_state) {

    $label = $this->options['label'] ? $this->options['label']: 'Chart Type';
    $selected_options = $this->options['chart_types'];
    $all_fields = $this->chartsTypes->getChartTypes();
    $options = array_filter($all_fields, function ($key) use ($selected_options) {
      return in_array($key, $selected_options, TRUE);
    }, ARRAY_FILTER_USE_KEY);

    $form['ct'] = [
      '#title' => $this->t('@value', ['@value' => $label]),
      '#type' => $this->options['exposed_select_type'],
      '#options' => $options,
      '#weight' => -20,
    ];

    if ($this->options['exposed_select_type'] == 'radios') {
      $form['ct']['#attributes']['class'] =
        [
          'chart-type-radios',
          'container-inline',
        ];
    }

    $form['ect'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['chart_types'] = ['default' => []];
    $options['exposed_select_type'] = ['default' => 'checkboxes'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['chart_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Chart Type Options'),
      '#description' => $this->t('Pick the chart type options to be exposed. You may need to disable your Views cache.'),
      '#options' => $this->chartsTypes->getChartTypes(),
      '#default_value' => $this->options['chart_types'],
    ];

    $form['exposed_select_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Exposed Selection Type'),
      '#description' => t('Choose your options widget.'),
      '#options' => [
        'radios' => $this->t('Radios'),
        'select' => $this->t('Single select'),
      ],
      '#default_value' => $this->options['exposed_select_type'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This is not a real field and it does not affect the query. But Views
    // won't render if the query() method is not present. This doesn't do
    // anything, but it has to be here. This function is a void so it doesn't
    // return anything.
  }

}
