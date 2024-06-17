<?php

namespace Drupal\nys_dashboard\Plugin\views\filter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
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
   * Date formatter service.
   *
   * @var Drupal\Core\Datetime\DateFormatter
   */
  public $dateFormatter;

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
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Date formatter service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactory $configFactory,
    DateFormatter $dateFormatter,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('exposed')) {
      $timezone_string = $this->configFactory->get('system.date')
        ->get('timezone')['default'];
      $timezone_object = new \DateTimeZone($timezone_string);
      $current_datetime = new \DateTime('now', $timezone_object);
      $current_year = $current_datetime->format('Y');
      $years = range($current_year, $current_year - 30);
      $form['value'] = [
        '#type' => 'select',
        '#title' => 'Year',
        '#options' => ['All' => '- Any -'] + array_combine($years, $years),
        '#default_value' => 'All',
      ];
      $form['year_month_filter__month'] = [
        '#type' => 'select',
        '#title' => 'Month',
        '#options' => [
          'All' => $this->t('- Any -'),
          'january' => $this->t('January'),
          'february' => $this->t('February'),
          'march' => $this->t('March'),
          'april' => $this->t('April'),
          'may' => $this->t('May'),
          'june' => $this->t('June'),
          'july' => $this->t('July'),
          'august' => $this->t('August'),
          'september' => $this->t('September'),
          'october' => $this->t('October'),
          'november' => $this->t('November'),
          'december' => $this->t('December'),
        ],
        '#default_value' => 'All',
        '#states' => [
          'visible' => [
            'select[name="year_month_filter"]' => ['!value' => 'All'],
          ],
        ],
      ];
    }
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
    // Set query value.
    $value = is_array($this->value) && !empty($this->value[0]) ? $this->value[0] : $this->value;
    $timezone_string = $this->configFactory->get('system.date')->get('timezone')['default'];
    $timezone_object = new \DateTimeZone($timezone_string);
    if ($this->month == 'All') {
      $start_datetime = new \DateTime("first day of january $value", $timezone_object);
      $following_year = $value + 1;
      $end_datetime = new \DateTime("first day of january $following_year", $timezone_object);
      $end_timestamp = $end_datetime->getTimestamp();
    }
    else {
      $start_datetime = new \DateTime("first day of $this->month $value", $timezone_object);
      $end_datetime = new \DateTime("last day of $this->month $value", $timezone_object);
      $end_timestamp = $end_datetime->getTimestamp() + 86400;
    }
    $start_timestamp = $start_datetime->getTimestamp();
    $this->value = [
      $this->dateFormatter->format($start_timestamp, 'html_datetime'),
      $this->dateFormatter->format($end_timestamp, 'html_datetime'),
    ];

    // Add where group to query that accounts for all primary date fields.
    $this->query->setWhereGroup('OR', 'nys_date_field_filter_group');
    $nys_date_tables_and_fields = [
      'node__field_date' => 'field_date_value',
      'node__field_date_range' => 'field_date_range_value',
      'node__field_ol_last_status_date' => 'field_ol_last_status_date_value',
      'node__field_ol_publish_date' => 'field_ol_publish_date_value',
    ];
    foreach ($nys_date_tables_and_fields as $table => $field) {
      $this->query->addTable($table);
      if ($table !== 'node__field_ol_publish_date') {
        $this->query->addWhere('nys_date_field_filter_group', "$table.$field", $this->value, 'BETWEEN');
      }
      else {
        $this->query->addWhere('nys_date_field_filter_group',
          ($this->query->getConnection()->condition('AND'))
            ->condition('node_field_data.type', 'bill')
            ->condition('node__field_ol_last_status_date.field_ol_last_status_date_value', '', 'IS NULL')
            ->condition("$table.$field", $this->value, 'BETWEEN')
        );
      }
    }
  }

}
