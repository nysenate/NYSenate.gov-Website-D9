<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsChart.
 *
 * @package Drupal\charts_chartjs\Settings\Chartjs
 */
class ChartjsTickConfigurationOptions implements \JsonSerializable {

  /**
   * If true, scale will include 0 if it is not already included.
   *
   * @var boolean
   */
  private $beginAtZero;

  /**
   * Maximum number of ticks and gridlines to show.
   *
   * @var int
   */
  private $maxTicksLimit = 11;

  /**
   * If defined and stepSize is not specified, the step size will be rounded to
   * this many decimal places.
   *
   * @var int
   */
  private $precision;

  /**
   * User defined fixed step size for the scale.
   *
   * @var int
   */
  private $stepSize;

  /**
   * Adjustment used when calculating the maximum data value.
   *
   * @var int
   */
  private $suggestedMax;

  /**
   * Adjustment used when calculating the minimum data value.
   *
   * @var int
   */
  private $suggestedMin;

  /**
   * @return bool
   */
  public function isBeginAtZero() {
    return $this->beginAtZero;
  }

  /**
   * @param bool $beginAtZero
   */
  public function setBeginAtZero(bool $beginAtZero) {
    $this->beginAtZero = $beginAtZero;
  }

  /**
   * @return int
   */
  public function getMaxTicksLimit() {
    return $this->maxTicksLimit;
  }

  /**
   * @param int $maxTicksLimit
   */
  public function setMaxTicksLimit(int $maxTicksLimit) {
    $this->maxTicksLimit = $maxTicksLimit;
  }

  /**
   * @return int
   */
  public function getPrecision() {
    return $this->precision;
  }

  /**
   * @param int $precision
   */
  public function setPrecision(int $precision) {
    $this->precision = $precision;
  }

  /**
   * @return int
   */
  public function getStepSize() {
    return $this->stepSize;
  }

  /**
   * @param int $stepSize
   */
  public function setStepSize(int $stepSize) {
    $this->stepSize = $stepSize;
  }

  /**
   * @return int
   */
  public function getSuggestedMax() {
    return $this->suggestedMax;
  }

  /**
   * @param int $suggestedMax
   */
  public function setSuggestedMax(int $suggestedMax) {
    $this->suggestedMax = $suggestedMax;
  }

  /**
   * @return int
   */
  public function getSuggestedMin() {
    return $this->suggestedMin;
  }

  /**
   * @param int $suggestedMin
   */
  public function setSuggestedMin(int $suggestedMin) {
    $this->suggestedMin = $suggestedMin;
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
