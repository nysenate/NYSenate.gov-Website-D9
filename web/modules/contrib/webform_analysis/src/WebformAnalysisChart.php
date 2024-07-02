<?php

namespace Drupal\webform_analysis;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;

/**
 * Webform Analysis Chart.
 */
class WebformAnalysisChart implements WebformAnalysisChartInterface {

  /**
   * The entity variable.
   *
   * @var \\Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The components variable.
   *
   * @var array
   */
  protected $components;

  /**
   * The field name variable.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The char type variable.
   *
   * @var string
   */
  protected $chartType;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $field_name
   *   Webform field name to display.
   * @param array $components
   *   Components.
   * @param string $chart_type
   *   Chart Type.
   */
  public function __construct(EntityInterface $entity, $field_name = NULL, array $components = [], $chart_type = '') {
    $this->entity = $entity;
    $this->fieldName = $field_name;
    $this->components = $components;
    $this->chartType = $chart_type;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$build = []) {
    if (!$this->entity) {
      return;
    }

    $analysis = new WebformAnalysis($this->entity, $this->fieldName);

    $build['components_data'] = $this->buildComponentsData();
    $buildComponents = &$build['components_data'];

    $charts = [];
    $header = $this->getHeader();

    foreach ($this->components as $component) {
      $id = $this->createComponentId($component);
      $chart = $this->createChart($id);
      $buildComponents['component__' . $component] = $this->buildComponentData($analysis, $component, $id);

      switch ($chart['type']) {
        case '':
          $buildComponent = &$buildComponents['component__' . $component];
          $buildComponent['#data']['#rows'] = $analysis->getComponentRows($component);
          break;

        case 'PieChart':
          $chart = $this->buildPieChart($analysis, $component, $header) + $chart;
          break;

        default:
          $chart['data'] = $analysis->getComponentRows($component, $header);
          break;
      }

      if ($chart['type'] && $chart['data']) {
        $charts[$id] = $chart;
      }
    }

    $build['#attached']['library'][] = 'webform_analysis/webform_charts';

    if ($charts) {
      $build['#attached'] += $this->buildAttachedSettings($charts);
    }

    $build['#cache'] = ['max-age' => 0];
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentsData() {
    return [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['webform-analysis-data'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentData(WebformAnalysisInterface $analysis, $component = '', $id = '') {
    $class_css = 'webform-chart--' . $component;
    return [
      '#theme' => 'webform_analysis_component',
      '#name'  => $component,
      '#title' => $analysis->getComponentTitle($component),
      '#data'  => [
        '#theme'  => 'table',
        '#prefix' => '<div id="' . $id . '" class="' . $class_css . '">',
        '#suffix' => '</div>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createComponentId($component) {
    return 'webform-chart--' . $component . '--' . Crypt::randomBytesBase64(8);
  }

  /**
   * {@inheritdoc}
   */
  public function createChart($id) {
    return [
      'type'     => $this->chartType,
      'options'  => ['title' => ''],
      'selector' => '#' . $id,
      'data'     => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return ['value', 'total'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPieChart(WebformAnalysisInterface $analysis, $component = '', array $header = []) {
    $data = $analysis->getComponentRows($component, $header, TRUE);
    $options = count($data) > 2 ? ['pieHole' => 0.2, 'title' => ''] : ['title' => ''];
    return [
      'options' => $options,
      'data'    => $data,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildAttachedSettings(array $charts = []) {
    return [
      'drupalSettings' => [
        'webformcharts' => [
          'packages' => ['corechart'],
          'charts'   => $charts,
        ],
      ],
    ];
  }

}
