<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Chart Legend.
 */
class ChartLegend implements \JsonSerializable {

  private $layout = 'vertical';

  private $align = 'right';

  private $verticalAlign = 'top';

  private $x = 0;

  private $y = 0;

  private $floating = TRUE;

  private $borderWidth = 1;

  private $backgroundColor = '#FCFFC5';

  private $shadow = TRUE;

  private $enabled = TRUE;

  private $itemStyle;

  private $rtl = FALSE;

  /**
   * Get Layout.
   *
   * @return string
   *   Layout.
   */
  public function getLayout() {
    return $this->layout;
  }

  /**
   * Set Layout.
   *
   * @param string $layout
   *   Layout.
   */
  public function setLayout($layout) {
    $this->layout = $layout;
  }

  /**
   * Get Align.
   *
   * @return string
   *   Align.
   */
  public function getAlign() {
    return $this->align;
  }

  /**
   * Set Align.
   *
   * @param string $align
   *   Align.
   */
  public function setAlign($align) {
    $this->align = $align;
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
   * Get X.
   *
   * @return int
   *   X.
   */
  public function getX() {
    return $this->x;
  }

  /**
   * Set X.
   *
   * @param int $x
   *   X.
   */
  public function setX($x) {
    $this->x = $x;
  }

  /**
   * Get Y.
   *
   * @return int
   *   Y.
   */
  public function getY() {
    return $this->y;
  }

  /**
   * Set Y.
   *
   * @param int $y
   *   Y.
   */
  public function setY($y) {
    $this->y = $y;
  }

  /**
   * Is Floating.
   *
   * @return bool
   *   Floating.
   */
  public function isFloating() {
    return $this->floating;
  }

  /**
   * Set Floating.
   *
   * @param bool $floating
   *   Floating.
   */
  public function setFloating($floating) {
    $this->floating = $floating;
  }

  /**
   * Get Border Width.
   *
   * @return int
   *   Border Width.
   */
  public function getBorderWidth() {
    return $this->borderWidth;
  }

  /**
   * Set Border Width.
   *
   * @param int $borderWidth
   *   Border Width.
   */
  public function setBorderWidth($borderWidth) {
    $this->borderWidth = $borderWidth;
  }

  /**
   * Get Background Color.
   *
   * @return string
   *   Background Color.
   */
  public function getBackgroundColor() {
    return $this->backgroundColor;
  }

  /**
   * Set Background Color.
   *
   * @param string $backgroundColor
   *   Background Color.
   */
  public function setBackgroundColor($backgroundColor) {
    $this->backgroundColor = $backgroundColor;
  }

  /**
   * Is Shadow.
   *
   * @return bool
   *   Shadow.
   */
  public function isShadow() {
    return $this->shadow;
  }

  /**
   * Set Shadow.
   *
   * @param bool $shadow
   *   Shadow.
   */
  public function setShadow($shadow) {
    $this->shadow = $shadow;
  }

  /**
   * Get Enabled.
   *
   * @return bool
   *   Enabled.
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * Set Enabled.
   *
   * @param bool $enabled
   *   Enabled.
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
  }

  /**
   * @return mixed
   */
  public function getItemStyle() {
    return $this->itemStyle;
  }

  /**
   * @param mixed $itemStyle
   */
  public function setItemStyle($itemStyle) {
    $this->itemStyle = $itemStyle;
  }

  /**
   * Get Direction.
   *
   * @return bool rtl false or true
   */
  public function getDirection() {
    return $this->rtl;
  }

  /**
   * Set Direction either rtl false or true.
   *
   * @param bool $rtl
   */
  public function setDirection($rtl) {
    $this->rtl = $rtl;
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
