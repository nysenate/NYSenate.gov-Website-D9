<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Chart Credits Position.
 */
class ChartCreditsPosition implements \JsonSerializable {

  private $align = 'center';

  private $verticalAlign = 'bottom';

  private $x;

  private $y;

  /**
   * @return mixed
   */
  public function getAlign() {
    return $this->align;
  }

  /**
   * @param mixed $align
   */
  public function setAlign($align) {
    $this->align = $align;
  }

  /**
   * @return mixed
   */
  public function getVerticalAlign() {
    return $this->verticalAlign;
  }

  /**
   * @param mixed $verticalAlign
   */
  public function setVerticalAlign($verticalAlign) {
    $this->verticalAlign = $verticalAlign;
  }

  /**
   * @return mixed
   */
  public function getX() {
    return $this->x;
  }

  /**
   * @param mixed $x
   */
  public function setX($x) {
    $this->x = $x;
  }

  /**
   * @return mixed
   */
  public function getY() {
    return $this->y;
  }

  /**
   * @param mixed $y
   */
  public function setY($y) {
    $this->y = $y;
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
