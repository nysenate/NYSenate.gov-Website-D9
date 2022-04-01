<?php

namespace Drupal\charts_google\Settings\Google;

/**
 * Class ChartArea.
 *
 * @package Drupal\charts_google\Settings\Google
 */
class ChartArea implements \JsonSerializable {

  /**
   * Chart area width.
   *
   * @var mixed
   */
  private $width;

  /**
   * Chart area height.
   *
   * @var mixed
   */
  private $height;

  /**
   * How far to draw the chart from the top border.
   *
   * @var mixed
   */
  private $top;

  /**
   * How far to draw the chart from the left border.
   *
   * @var mixed
   */
  private $left;

  /**
   * Gets the chart area width.
   *
   * @return mixed
   *   Width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Sets the chart area width.
   *
   * @param mixed $width
   *   Width.
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  /**
   * Gets the chart area height.
   *
   * @return mixed
   *   Height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Sets the chart area height.
   *
   * @param mixed $height
   *   Height.
   */
  public function setHeight($height) {
    $this->height = $height;
  }

  /**
   * Gets how far to draw the chart from the top border.
   *
   * @return mixed
   *   Padding Top.
   */
  public function getPaddingTop() {
    return $this->top;
  }

  /**
   * Sets how far to draw the chart from the top border.
   *
   * @param mixed $top
   *   Padding Top.
   */
  public function setPaddingTop($top) {
    $this->top = $top;
  }

  /**
   * Gets how far to draw the chart from the left border.
   *
   * @return mixed
   *   Padding Left.
   */
  public function getPaddingLeft() {
    return $this->left;
  }

  /**
   * Sets how far to draw the chart from the left border.
   *
   * @param mixed $left
   *   Padding Left.
   */
  public function setPaddingLeft($left) {
    $this->left = $left;
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
