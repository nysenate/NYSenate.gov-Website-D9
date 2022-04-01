<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsScales.
 *
 * @package Drupal\charts_chartjs\Settings\Chartjs
 */
class ChartjsScales implements \JsonSerializable {

  /**
   * xAxes array.
   *
   * @var mixed
   */
  private $xAxes;

  /**
   * yAxes array.
   *
   * @var mixed
   */
  private $yAxes;

  /**
   * @return mixed
   */
  public function getXAxes() {
    return $this->xAxes;
  }

  /**
   * @param mixed $xAxes
   */
  public function setXAxes($xAxes) {
    $this->xAxes = $xAxes;
  }

  /**
   * @return mixed
   */
  public function getYAxes() {
    return $this->yAxes;
  }

  /**
   * @param mixed $yAxes
   */
  public function setYAxes($yAxes) {
    $this->yAxes = $yAxes;
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
