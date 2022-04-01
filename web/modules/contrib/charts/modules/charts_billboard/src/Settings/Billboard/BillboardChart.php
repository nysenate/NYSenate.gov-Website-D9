<?php

namespace Drupal\charts_billboard\Settings\Billboard;

/**
 * BillboardChart.
 */
class BillboardChart implements \JsonSerializable {

  /**
   * Color.
   *
   * @var mixed
   */
  private $color;

  /**
   * BindTo.
   *
   * @var string
   */
  private $bindto;

  /**
   * Data.
   *
   * @var mixed
   */
  private $data;

  /**
   * Axis.
   *
   * @var mixed
   */
  private $axis;

  /**
   * Chart title.
   *
   * @var mixed
   */
  private $title;

  /**
   * Gauge.
   *
   * @var mixed
   */
  private $gauge;

  /**
   * Point.
   *
   * @var mixed
   */
  private $point;

  /**
   * Legend.
   *
   * @var mixed
   */
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
   * Get the gauge.
   *
   * @return mixed
   *   Gauge.
   */
  public function getGauge() {
    return $this->gauge;
  }

  /**
   * Set the gauge.
   *
   * @param mixed $gauge
   *   Gauge.
   */
  public function setGauge($gauge) {
    $this->gauge = $gauge;
  }

  /**
   * Get the point.
   *
   * @return mixed
   *   Point.
   */
  public function getPoint() {
    return $this->point;
  }

  /**
   * Set the point.
   *
   * @param mixed $point
   *   Point.
   */
  public function setPoint($point) {
    $this->point = $point;
  }

  /**
   * Get the legend.
   *
   * @return mixed
   *   Legend.
   */
  public function getLegend() {
    return $this->legend;
  }

  /**
   * Set the legend.
   *
   * @param mixed $legend
   *   Legend.
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
