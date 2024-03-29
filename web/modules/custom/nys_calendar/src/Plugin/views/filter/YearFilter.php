<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Year filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("year_filter")
 */
class YearFilter extends FilterPluginBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  public $configFactory;

  /**
   * Constructs a YearFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $timezone_string = $this->configFactory->get('system.date')->get('timezone')['default'];
    $timezone_object = new \DateTimeZone($timezone_string);
    $current_datetime = new \DateTime('now', $timezone_object);
    $current_year = $current_datetime->format('Y');
    $years = range($current_year, $current_year - 30);
    $static_options = [
      'all' => '- Any -',
      'current_year' => 'Current year',
    ];
    $dynamic_options = array_combine($years, $years);
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Selected year'),
      '#options' => $static_options + $dynamic_options,
      '#default_value' => !empty($this->options['value']) ? $this->options['value'] : 'all',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function operatorForm(&$form, FormStateInterface $form_state) {
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Show content from'),
      '#options' => [
        'all' => '- Any -',
        '=' => 'Selected year',
        '>=' => 'After start of selected year',
        '<' => 'Before start of selected year',
      ],
      '#default_value' => !empty($this->options['operator']) ? $this->options['operator'] : 'all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['use_operator']['#default_value'] = TRUE;
    foreach ($form['expose'] as $expose_field_key => $expose_field) {
      if (!empty($form['expose'][$expose_field_key]['#type'])) {
        $form['expose'][$expose_field_key]['#access'] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (
      empty($this->options['expose']['operator_id'])
      || empty($this->options['expose']['identifier'])
    ) {
      return;
    }

    // Build operator form element.
    $operator = $this->options['expose']['operator_id'];
    $op_wrapper = $this->options['expose']['identifier'] . '_wrapper';
    $this->buildValueWrapper($form, $op_wrapper);
    $this->operatorForm($form, $form_state);
    $form[$op_wrapper][$operator] = $form['operator'];
    unset($form['operator']);

    // Build value form element.
    $value = $this->options['expose']['identifier'];
    $val_wrapper = $value . '_wrapper';
    $this->buildValueWrapper($form, $val_wrapper);
    $this->valueForm($form, $form_state);
    $form[$val_wrapper][$value] = $form['value'];
    unset($form['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Make input value consistent regardless of filter context.
    $this->value = is_array($this->value) && !empty($this->value[0]) ? $this->value[0] : $this->value;

    // Bypass query additions if either field set to 'all'.
    if ($this->value == 'all' || $this->operator == 'all') {
      return;
    }

    $timezone_string = $this->configFactory->get('system.date')->get('timezone')['default'];
    $timezone_object = new \DateTimeZone($timezone_string);

    // Dynamically set 'current_year' to current year.
    if ($this->value == 'current_year') {
      $current_year = new \DateTime("now", $timezone_object);
      $this->value = $current_year->format('Y');
    }

    // Process value(s) and operator for datetime fields.
    if ($this->configuration['field_type'] == 'datetime') {
      $selected_year_start = new \DateTime("first day of january $this->value", $timezone_object);

      if ($this->operator == '=') {
        $this->operator = 'BETWEEN';
        $following_year = $this->value + 1;
        $next_year_start = new \DateTime("first day of january $following_year", $timezone_object);
        $this->value = [
          $selected_year_start->getTimestamp(),
          $next_year_start->getTimestamp(),
        ];
      }
      else {
        $this->value = $selected_year_start->getTimestamp();
      }
    }

    parent::query();
  }

}
