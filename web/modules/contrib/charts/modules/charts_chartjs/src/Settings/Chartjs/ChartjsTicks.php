<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsChart.
 *
 * @package Drupal\charts_chartjs\Settings\Chartjs
 */
class ChartjsTicks implements \JsonSerializable {

  /**
   * ...
   *
   * @var mixed
   */
  private $ticks;

  /**
   * Set Y-axis as stacked or not.
   *
   * @var bool
   */
  private $stacked;

  /**
   * @return mixed
   */
  public function getTicks() {
    return $this->ticks;
  }

  /**
   * @param mixed $ticks
   */
  public function setTicks($ticks) {
    $this->ticks = $ticks;
  }

  /**
   * @return bool
   */
  public function isStacked() {
    return $this->stacked;
  }

  /**
   * @param bool $stacked
   */
  public function setStacked(bool $stacked) {
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
