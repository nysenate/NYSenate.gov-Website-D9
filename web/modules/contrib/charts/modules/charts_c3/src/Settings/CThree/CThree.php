<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * CThree.
 */
class CThree implements \JsonSerializable {

  private $color;

  private $bindto;

  private $data;

  private $axis;

  private $title;

  private $gauge;

  private $point;

  private $legend;

  /**
   * Get Title.
   *
   * @return mixed
   *   Title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Set Title.
   *
   * @param mixed $title
   *   Title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Get Axis.
   *
   * @return mixed
   *   Axis.
   */
  public function getAxis() {
    return $this->axis;
  }

  /**
   * Set Axis.
   *
   * @param mixed $axis
   *   Axis.
   */
  public function setAxis($axis) {
    $this->axis = $axis;
  }

  /**
   * Get Data.
   *
   * @return mixed
   *   Data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Set Data.
   *
   * @param mixed $data
   *   Data.
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * Get Bind to.
   *
   * @return string
   *   Bind to.
   */
  public function getBindTo() {
    return $this->bindto;
  }

  /**
   * Set Bind to.
   *
   * @param mixed $bindto
   *   Bind to.
   */
  public function setBindTo($bindto) {
    $this->bindto = $bindto;
  }

  /**
   * Get Color.
   *
   * @return mixed
   *   Color.
   */
  public function getColor() {
    return $this->color;
  }

  /**
   * Set Color.
   *
   * @param mixed $color
   *   Color.
   */
  public function setColor($color) {
    $this->color = $color;
  }

  /**
   * @return mixed
   */
  public function getGauge() {
    return $this->gauge;
  }

  /**
   * @param mixed $gauge
   */
  public function setGauge($gauge) {
    $this->gauge = $gauge;
  }

  /**
   * @return mixed
   */
  public function getPoint() {
    return $this->point;
  }

  /**
   * @param mixed $point
   */
  public function setPoint($point) {
    $this->point = $point;
  }

  /**
   * @return mixed
   */
  public function getLegend() {
    return $this->legend;
  }

  /**
   * @param mixed $legend
   */
  public function setLegend($legend) {
    $this->legend = $legend;
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
