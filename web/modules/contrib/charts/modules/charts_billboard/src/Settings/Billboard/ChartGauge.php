<?php

namespace Drupal\charts_billboard\Settings\Billboard;

/**
 * Gauge settings.
 */
class ChartGauge implements \JsonSerializable {

  private $min;

  private $max;

  private $units;

  /**
   * @return mixed
   */
  public function getMax() {
    return $this->max;
  }

  /**
   * @param mixed $max
   */
  public function setMax($max) {
    $this->max = $max;
  }

  /**
   * @return mixed
   */
  public function getMin() {
    return $this->min;
  }

  /**
   * @param mixed $min
   */
  public function setMin($min) {
    $this->min = $min;
  }

  /**
   * @return mixed
   */
  public function getUnits() {
    return $this->units;
  }

  /**
   * @param mixed $units
   */
  public function setUnits($units) {
    $this->units = $units;
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
