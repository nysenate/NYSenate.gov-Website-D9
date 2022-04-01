<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Chart Legend.
 */
class ChartLegendItemStyle implements \JsonSerializable {

  private $color;

  private $cursor = 'pointer';

  private $fontSize = '12px';

  private $fontWeight = 'bold';

  private $textOverflow;

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
   * @return string
   */
  public function getCursor() {
    return $this->cursor;
  }

  /**
   * @param string $cursor
   */
  public function setCursor($cursor) {
    $this->cursor = $cursor;
  }

  /**
   * @return string
   */
  public function getFontSize() {
    return $this->fontSize;
  }

  /**
   * @param string $fontSize
   */
  public function setFontSize($fontSize) {
    $this->fontSize = $fontSize;
  }

  /**
   * @return string
   */
  public function getFontWeight() {
    return $this->fontWeight;
  }

  /**
   * @param string $fontWeight
   */
  public function setFontWeight($fontWeight) {
    $this->fontWeight = $fontWeight;
  }

  /**
   * @return mixed
   */
  public function getTextOverflow() {
    return $this->textOverflow;
  }

  /**
   * @param mixed $textOverflow
   */
  public function setTextOverflow($textOverflow) {
    $this->textOverflow = $textOverflow;
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
