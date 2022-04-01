<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Chart Title.
 */
class ChartTitle implements \JsonSerializable {

  private $text;

  private $y;

  private $verticalAlign;

  /**
   * Get Text.
   *
   * @return mixed
   *   Text.
   */
  public function getText() {
    return $this->text;
  }

  /**
   * Set Text.
   *
   * @param mixed $text
   *   Text.
   */
  public function setText($text) {
    $this->text = $text;
  }

  /**
   * Get VerticalOffset.
   *
   * @return integer
   *   VerticalOffset.
   */
  public function getVerticalOffset() {
    return $this->y;
  }

  /**
   * Set VerticalOffset.
   *
   * @param integer
   *   VerticalOffset.
   */
  public function setVerticalOffset($y) {
    $this->y = $y;
  }

  /**
   * Get Vertical Align.
   *
   * @return string
   *   Vertical Align.
   */
  public function getVerticalAlign() {
    return $this->verticalAlign;
  }

  /**
   * Set Vertical Align.
   *
   * @param string $verticalAlign
   *   Vertical Align.
   */
  public function setVerticalAlign($verticalAlign) {
    $this->verticalAlign = $verticalAlign;
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
