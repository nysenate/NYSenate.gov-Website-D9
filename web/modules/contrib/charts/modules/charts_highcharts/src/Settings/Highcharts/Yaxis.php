<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Y Axis.
 */
class Yaxis implements \JsonSerializable {

  private $title;

  private $labels = '';

  private $plotBands = NULL;

  private $min;

  private $max;

  private $categories = NULL;

  private $tickInterval;

  private $tickmarkPlacement;

  private $showFirstLabel;

  private $startOnTick;

  private $showLastLabel;

  private $endOnTick;

  private $gridLineInterpolation = 'circle';

  // Default is 0 per https://api.highcharts.com/highcharts/yAxis.lineWidth .
  private $lineWidth = 0;

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
   * @return string
   *   Labels.
   */
  public function getLabels() {
    return $this->labels;
  }

  /**
   * Set Labels.
   *
   * @param string $labels
   *   Labels.
   */
  public function setLabels($labels) {
    $this->labels = $labels;
  }

  /**
   * @return array
   */
  public function getPlotBands() {
    return $this->plotBands;
  }

  /**
   * @param array $plotBands
   */
  public function setPlotBands($plotBands) {
    $this->plotBands = $plotBands;
  }

  /**
   * @return float
   */
  public function getMin() {
    return $this->min;
  }

  /**
   * @param float $min
   */
  public function setMin(float $min) {
    $this->min = $min;
  }

  /**
   * @return float
   */
  public function getMax() {
    return $this->max;
  }

  /**
   * @param float $max
   */
  public function setMax(float $max) {
    $this->max = $max;
  }

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
   * @return mixed
   */
  public function getTickInterval() {
    return $this->tickInterval;
  }

  /**
   * @param mixed $tickInterval
   */
  public function setTickInterval($tickInterval) {
    $this->tickInterval = $tickInterval;
  }

  /**
   * @return mixed
   */
  public function getShowFirstLabel() {
    return $this->showFirstLabel;
  }

  /**
   * @param mixed $showFirstLabel
   */
  public function setShowFirstLabel($showFirstLabel) {
    $this->showFirstLabel = $showFirstLabel;
  }

  /**
   * @return mixed
   */
  public function getStartOnTick() {
    return $this->startOnTick;
  }

  /**
   * @param mixed $startOnTick
   */
  public function setStartOnTick($startOnTick) {
    $this->startOnTick = $startOnTick;
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
   * @return mixed
   */
  public function getShowLastLabel() {
    return $this->showLastLabel;
  }

  /**
   * @param mixed $showLastLabel
   */
  public function setShowLastLabel($showLastLabel) {
    $this->showLastLabel = $showLastLabel;
  }

  /**
   * @return mixed
   */
  public function getEndOnTick() {
    return $this->endOnTick;
  }

  /**
   * @param mixed $endOnTick
   */
  public function setEndOnTick($endOnTick) {
    $this->endOnTick = $endOnTick;
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
   * @return mixed
   */
  public function getGridLineInterpolation() {
    return $this->gridLineInterpolation;
  }

  /**
   * @param mixed $gridLineInterpolation
   */
  public function setGridLineInterpolation($gridLineInterpolation) {
    $this->gridLineInterpolation = $gridLineInterpolation;
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
