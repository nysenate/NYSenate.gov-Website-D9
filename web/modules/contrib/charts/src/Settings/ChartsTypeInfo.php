<?php

namespace Drupal\charts\Settings;

use Drupal\charts\Theme\ChartsInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class ChartsTypeInfo {

  use StringTranslationTrait;

  public function __construct() {
    $translation = \Drupal::service('string_translation');
    $this->setStringTranslation($translation);
  }

  /**
   * Get charts types info.
   *
   * @return array
   *   Chart types.
   */
  public function chartsChartsTypeInfo() {
    $chart_types['area'] = [
      'label' => $this->t('Area'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'stacking' => TRUE,
    ];
    $chart_types['bar'] = [
      'label' => $this->t('Bar'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'axis_inverted' => TRUE,
      'stacking' => TRUE,
    ];
    $chart_types['column'] = [
      'label' => $this->t('Column'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'stacking' => TRUE,
    ];
    $chart_types['donut'] = [
      'label' => $this->t('Donut'),
      'axis' => ChartsInterface::CHARTS_SINGLE_AXIS,
    ];
    $chart_types['gauge'] = [
      'label' => $this->t('Gauge'),
      'axis' => ChartsInterface::CHARTS_SINGLE_AXIS,
      'stacking' => FALSE,
    ];
    $chart_types['line'] = [
      'label' => $this->t('Line'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    ];
    $chart_types['pie'] = [
      'label' => $this->t('Pie'),
      'axis' => ChartsInterface::CHARTS_SINGLE_AXIS,
    ];
    $chart_types['scatter'] = [
      'label' => $this->t('Scatter'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    ];
    $chart_types['spline'] = [
      'label' => $this->t('Spline'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    ];

    return $chart_types;
  }

  public function getChartTypes() {
    $chart_types = $this->chartsChartsTypeInfo();
    $type_options = [];
    foreach ($chart_types as $chart_type => $chart_type_info) {
      $type_options[$chart_type] = $chart_type_info['label'];
    }

    return $type_options;
  }

  /**
   * Retrieve a specific chart type.
   *
   * @param string $chart_type
   *   The type of chart selected for display.
   *
   * @return mixed
   *   If not false, returns an array of values from charts_charts_type_info.
   */
  public function getChartType($chart_type) {
    $types = $this->chartsChartsTypeInfo();

    return (isset($types[$chart_type])) ? $types[$chart_type] : FALSE;
  }

}
