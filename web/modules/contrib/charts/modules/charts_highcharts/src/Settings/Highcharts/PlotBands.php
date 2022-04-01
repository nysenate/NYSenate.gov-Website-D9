<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * PlotBands for gauge charts.
 */
class PlotBands implements \JsonSerializable {

  private $from;

  private $to;

  private $color;

  /**
   * @return mixed
   */
  public function getFrom() {
    return $this->from;
  }

  /**
   * @param mixed $from
   */
  public function setFrom($from) {
    $this->from = $from;
  }

  /**
   * @return mixed
   */
  public function getTo() {
    return $this->to;
  }

  /**
   * @param mixed $to
   */
  public function setTo($to) {
    $this->to = $to;
  }

  /**
   * @return string
   */
  public function getColor() {
    return $this->color;
  }

  /**
   * @param string $color
   */
  public function setColor($color) {
    $this->color = $color;
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
