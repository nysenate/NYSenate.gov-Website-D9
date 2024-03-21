<?php

namespace Drupal\nys_dashboard\Plugin\views\filter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Year/month filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("year_month_filter")
 */
class YearMonthFilter extends FilterPluginBase {

  /**
   * The optional month value.
   *
   * @var string
   */
  public $month = 'All';

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  public $configFactory;

  /**
   * Constructs a YearMonthFilter object.
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
    $form['value'] = [
      '#type' => 'select',
      '#title' => 'Year',
      '#options' => array_combine($years, $years),
    ];
    $form['year_month_filter__month'] = [
      '#type' => 'select',
      '#title' => 'Month',
      '#options' => [
        'All' => '- Any -',
        'january' => 'January',
        'february' => 'February',
        'march' => 'March',
        'april' => 'April',
        'may' => 'May',
        'june' => 'June',
        'july' => 'July',
        'august' => 'August',
        'september' => 'September',
        'october' => 'October',
        'november' => 'November',
        'december' => 'December',
      ],
      '#states' => [
        'visible' => [
          'select[name="year_month_filter"]' => ['!value' => 'All'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storeExposedInput($input, $status) {
    parent::storeExposedInput($input, $status);
    $this->month = $input['year_month_filter__month'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);

    // Ensure month element appears after year in exposed form.
    $month_index = array_search('year_month_filter__month', array_keys($form));
    $year_index = array_search('year_month_filter', array_keys($form));
    if ($month_index !== FALSE && $year_index !== FALSE) {
      $month_form_element = array_splice($form, $month_index, 1);
      $new_month_index = $year_index + 1;
      $form = array_slice($form, 0, $new_month_index, TRUE) + $month_form_element + array_slice($form, $new_month_index, NULL, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Make input value consistent regardless of filter context.
    $this->value = is_array($this->value) && !empty($this->value[0]) ? $this->value[0] : $this->value;
    $this->operator = 'BETWEEN';
    $timezone_string = $this->configFactory->get('system.date')->get('timezone')['default'];
    $timezone_object = new \DateTimeZone($timezone_string);

    if ($this->month == 'All') {
      $start_datetime = new \DateTime("first day of january $this->value", $timezone_object);
      $following_year = $this->value + 1;
      $end_datetime = new \DateTime("first day of january $following_year", $timezone_object);
    }
    else {
      $start_datetime = new \DateTime("first day of $this->month $this->value", $timezone_object);
      $end_datetime = new \DateTime("last day of $this->month $this->value", $timezone_object);
    }
    $this->value = [
      $start_datetime->getTimestamp(),
      ($this->month == 'All') ? $end_datetime->getTimestamp() : $end_datetime->getTimestamp() + 86400,
    ];

    parent::query();
  }

}
