<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsStacking.
 *
 * @package Drupal\charts_chartjs\Settings\Chartjs
 */
class ChartjsStacking implements \JsonSerializable {

  /**
   * Stacking option.
   *
   * @var boolean
   */
  private $stacked;

  /**
   * Gets stacking chart option.
   *
   * @return mixed
   *   Stacking option.
   */
  public function getStacking() {
    return $this->stacked;
  }

  /**
   * Sets stacking chart option.
   *
   * @param mixed $stacked
   *   Stacking option.
   */
  public function setStacking($stacked) {
    $this->stacked = $stacked;
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
