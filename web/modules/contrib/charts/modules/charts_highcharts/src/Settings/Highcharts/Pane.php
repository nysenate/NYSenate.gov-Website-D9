<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Pane for gauge charts.
 */
class Pane implements \JsonSerializable {

  private $startAngle = -150;

  private $endAngle = 150;

  /**
   * @return int
   */
  public function getStartAngle() {
    return $this->startAngle;
  }

  /**
   * @param int $startAngle
   */
  public function setStartAngle($startAngle) {
    $this->startAngle = $startAngle;
  }

  /**
   * @return int
   */
  public function getEndAngle() {
    return $this->endAngle;
  }

  /**
   * @param int $endAngle
   */
  public function setEndAngle($endAngle) {
    $this->endAngle = $endAngle;
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
