<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * X Axis.
 */
class Xaxis implements \JsonSerializable {

  private $categories = [];

  private $title;

  private $labels;

  private $tickmarkPlacement;

  private $min;

  private $max;

  private $tickInterval;

  // Default is 1 per https://api.highcharts.com/highcharts/xAxis.lineWidth .
  private $lineWidth = 1;

  /**
   * Get Categories.
   *
   * @return array
   *   Categories.
   */
  public function getCategories() {
    return $this->categories;
  }

  /**
   * Set Categories.
   *
   * @param mixed $categories
   *   Categories.
   */
  public function setCategories($categories) {
    $this->categories = $categories;
  }

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
   * Get Labels.
   *
   * @return mixed
   *   Labels.
   */
  public function getLabels() {
    return $this->labels;
  }

  /**
   * Set Labels.
   *
   * @param mixed $labels
   *   Labels.
   */
  public function setLabels($labels) {
    $this->labels = $labels;
  }

  /**
   * @return mixed
   */
  public function getTickmarkPlacement() {
    return $this->tickmarkPlacement;
  }

  /**
   * @param mixed $tickmarkPlacement
   */
  public function setTickmarkPlacement($tickmarkPlacement) {
    $this->tickmarkPlacement = $tickmarkPlacement;
  }

  /**
   * @return int
   */
  public function getMin() {
    return $this->min;
  }

  /**
   * @param int $min
   */
  public function setMin($min) {
    $this->min = $min;
  }

  /**
   * @return int
   */
  public function getMax() {
    return $this->max;
  }

  /**
   * @param int $max
   */
  public function setMax($max) {
    $this->max = $max;
  }

  /**
   * @return int
   */
  public function getTickInterval() {
    return $this->tickInterval;
  }

  /**
   * @param int $tickInterval
   */
  public function setTickInterval($tickInterval) {
    $this->tickInterval = $tickInterval;
  }

  /**
   * @return int
   */
  public function getLineWidth() {
    return $this->lineWidth;
  }

  /**
   * @param int $lineWidth
   */
  public function setLineWidth($lineWidth) {
    $this->lineWidth = $lineWidth;
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
