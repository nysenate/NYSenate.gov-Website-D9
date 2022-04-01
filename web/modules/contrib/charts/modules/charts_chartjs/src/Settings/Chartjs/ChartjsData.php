<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsData.
 *
 * @package Drupal\charts_chartjs\Settings\Chartjs
 *
 *  Chart.js data options are described here:
 * @see http://www.chartjs.org/docs/latest/getting-started/
 */
class ChartjsData implements \JsonSerializable {

  /**
   * Property that specifies the labels.
   *
   * @var mixed
   */
  private $labels;

  /**
   * An object that specifies the datasets.
   *
   * @var mixed
   */
  private $datasets;

  /**
   * Get Chart Axis property that specifies a title for the axis.
   *
   * @return mixed
   *   Labels.
   */
  public function getLabels() {
    return $this->labels;
  }

  /**
   * Set Labels property.
   *
   * @param mixed $labels
   *   Labels.
   */
  public function setLabels($labels) {
    $this->labels = $labels;
  }

  /**
   * Get an object that specifies the datasets.
   *
   * @return mixed
   *   Datasets.
   */
  public function getDatasets() {
    return $this->datasets;
  }

  /**
   * Set an object that specifies the datasets.
   *
   * @param mixed $datasets
   *   Datasets.
   */
  public function setDatasets($datasets) {
    $this->datasets = $datasets;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Json Serialize.
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);
    return $vars;
  }

}
