<?php

namespace Drupal\geolocation\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\LocationInputManager;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geolocation\ProximityTrait;

/**
 * Filter handler for search keywords.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("geolocation_filter_proximity")
 */
class ProximityFilter extends NumericFilter implements ContainerFactoryPluginInterface {

  use ProximityTrait;

  /**
   * Proximity center manager.
   *
   * @var \Drupal\geolocation\LocationInputManager
   */
  protected $locationInputManager;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\geolocation\LocationInputManager $location_input_manager
   *   Proximity center manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocationInputManager $location_input_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->locationInputManager = $location_input_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.locationinput')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    // Add source, lat, lng and filter.
    $options = parent::defineOptions();

    $options['location_input'] = ['default' => []];
    $options['unit'] = ['default' => 'km'];

    $options['value']['contains']['center'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['unit'] = [
      '#title' => $this->t('Distance unit'),
      '#description' => $this->t('Unit to use for conversion of input value to proximity distance.'),
      '#type' => 'select',
      '#default_value' => $this->options['unit'],
      '#weight' => 6,
      '#options' => [
        'km' => $this->t('Kilometers'),
        'mi' => $this->t('Miles'),
        'nm' => $this->t('Nautical Miles'),
      ],
    ];

    $form['location_input'] = $this->locationInputManager->getOptionsForm($this->options['location_input'], $this);
  }

  /**
   * {@inheritdoc}
   */
  public function groupForm(&$form, FormStateInterface $form_state) {
    parent::groupForm($form, $form_state);

    $center_form = $this->locationInputManager->getForm($this->options['location_input'], $this, empty($this->value['center']) ? NULL : $this->value['center']);
    if (!empty($center_form)) {
      $form['center'] = $center_form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $form['#tree'] = TRUE;

    if (!isset($form['value']['value'])) {
      $form['value'] = array_replace($form['value'], [
        '#type' => 'number',
        '#min' => 0,
        '#step' => 0.1,
        '#title' => $this->t('Distance'),
        '#description' => $this->t('Distance in %unit', ['%unit' => $this->options['unit'] === 'km' ? $this->t('Kilometers') : $this->t('Miles')]),
        '#default_value' => $form['value']['#default_value'],
      ]);
    }
    else {
      $form['value']['value'] = array_replace($form['value']['value'], [
        '#type' => 'number',
        '#min' => 0,
        '#step' => 0.1,
        '#title' => $this->t('Distance'),
        '#description' => $this->t('Distance in %unit', ['%unit' => $this->options['unit'] === 'km' ? $this->t('Kilometers') : $this->t('Miles')]),
        '#default_value' => $form['value']['value']['#default_value'],
      ]);
    }

    $form['center'] = $this->locationInputManager->getForm($this->options['location_input'], $this, empty($this->value['center']) ? NULL : $this->value['center']);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    $value = $form_state->getValue(['options', 'value', 'value']);
    $distance = (float) $value;
    $form_state->setValue(['options', 'value', 'value'], $distance);
    $form_state->setValue(
      ['options', 'value', 'center'],
      $form_state->getValue(['options', 'value', 'center'], [])
    );

    parent::valueSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    parent::acceptExposedInput($input);

    if (
      array_key_exists('lat', $input)
      && $input['lat'] !== ''
      && array_key_exists('lng', $input)
      && $input['lng'] !== ''
    ) {
      $this->value['lat'] = (float) $input['lat'];
      $this->value['lng'] = (float) $input['lng'];
    }

    if (!empty($input['center'])) {
      $this->value['center'] = $input['center'];
    }
    else {
      $this->value['center'] = [];
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();
    $this->value['value'] = self::convertDistance($this->value['value'], $this->options['unit']);

    if (
      array_key_exists('lat', $this->value)
      && array_key_exists('lng', $this->value)
    ) {
      $center = [
        'lat' => (float) $this->value['lat'],
        'lng' => (float) $this->value['lng'],
      ];
    }
    else {
      $center = $this->locationInputManager->getCoordinates((array) $this->value['center'], $this->options['location_input'], $this);
    }

    if (
      empty($center)
      || !is_numeric($center['lat'])
      || !is_numeric($center['lng'])
      || empty($this->value['value'])
    ) {
      return;
    }

    // Build the query expression.
    $expression = self::getProximityQueryFragment($table, $this->realField, $center['lat'], $center['lng']);

    // Get operator info.
    $info = $this->operators();

    // Make sure a callback exists and add a where expression for the chosen
    // operator.
    if (!empty($info[$this->operator]['method']) && method_exists($this, $info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($expression);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($expression) {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    if ($this->operator == 'between') {
      $query->addWhereExpression($this->options['group'], $expression . ' BETWEEN ' . $this->value['min'] . ' AND ' . $this->value['max']);
    }
    else {
      $query->addWhereExpression($this->options['group'], $expression . ' NOT BETWEEN ' . $this->value['min'] . ' AND ' . $this->value['max']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($expression) {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $query->addWhereExpression($this->options['group'], $expression . ' ' . $this->operator . ' ' . $this->value['value']);
  }

  /**
   * {@inheritdoc}
   */
  protected function opEmpty($expression) {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $query->addWhereExpression($this->options['group'], $expression . ' ' . $operator);
  }

  /**
   * {@inheritdoc}
   */
  protected function opRegex($expression) {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $query->addWhereExpression($this->options['group'], $expression . ' ~* ' . $this->value['value']);
  }

}
