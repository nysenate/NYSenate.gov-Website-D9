<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Highcharts.
 */
class HighchartsOptions implements \JsonSerializable {

  private $chart;

  private $title;

  private $subtitle;

  private $xAxis;

  private $yAxis;

  private $tooltip;

  private $plotOptions;

  private $legend;

  private $credits;

  private $innerSize = '%20';

  private $series;

  private $exporting;

  private $pane;

  /**
   * Get Chart.
   *
   * @return mixed
   *   Chart.
   */
  public function getChart() {
    return $this->chart;
  }

  /**
   * Set Chart.
   *
   * @param mixed $chart
   *   Chart.
   */
  public function setChart($chart) {
    $this->chart = $chart;
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
   * Get X Axis.
   *
   * @return mixed
   *   X Axis.
   */
  public function getAxisX() {
    return $this->xAxis;
  }

  /**
   * Set X Axis.
   *
   * @param mixed $xAxis
   *   X Axis.
   */
  public function setAxisX($xAxis) {
    $this->xAxis = $xAxis;
  }

  /**
   * Get Y Axis.
   *
   * @return array $yAxis
   *   Y Axis.
   */
  public function getAxisY() {
    return $this->yAxis;
  }

  /**
   * Set Y Axis.
   *
   * @param array $yAxis
   *   Y Axis.
   */
  public function setAxisY($yAxis) {
    $this->yAxis = $yAxis;
  }

  /**
   * Get Tooltip.
   *
   * @return mixed
   *   Tooltip.
   */
  public function getTooltip() {
    return $this->tooltip;
  }

  /**
   * Set Tooltip.
   *
   * @param mixed $tooltip
   *   Tooltip.
   */
  public function setTooltip($tooltip) {
    $this->tooltip = $tooltip;
  }

  /**
   * Get Plot Options.
   *
   * @return mixed
   *   Plot Options.
   */
  public function getPlotOptions() {
    return $this->plotOptions;
  }

  /**
   * Set Plot Options.
   *
   * @param mixed $plotOptions
   *   Plot Options.
   */
  public function setPlotOptions($plotOptions) {
    $this->plotOptions = $plotOptions;
  }

  /**
   * Get Legend.
   *
   * @return mixed
   *   Legend.
   */
  public function getLegend() {
    return $this->legend;
  }

  /**
   * Set Legend.
   *
   * @param mixed $legend
   *   Legend.
   */
  public function setLegend($legend) {
    $this->legend = $legend;
  }

  /**
   * Get Credits.
   *
   * @return mixed
   *   Credits.
   */
  public function getCredits() {
    return $this->credits;
  }

  /**
   * Set Credits.
   *
   * @param mixed $credits
   *   Credits.
   */
  public function setCredits($credits) {
    $this->credits = $credits;
  }

  /**
   * Get Inner Size.
   *
   * @return mixed
   *   Inner Size.
   */
  public function getInnerSize() {
    return $this->innerSize;
  }

  /**
   * Set Inner Size.
   *
   * @param mixed $innerSize
   *   Inner Size.
   */
  public function setInnerSize($innerSize) {
    $this->innerSize = $innerSize;
  }

  /**
   * Get Series.
   *
   * @return mixed
   *   Series.
   */
  public function getSeries() {
    return $this->series;
  }

  /**
   * Set Series.
   *
   * @param mixed $series
   *   Series.
   */
  public function setSeries($series) {
    $this->series = $series;
  }

  /**
   * Get exporting options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\ExportingOptions|null
   *   The exporting options.
   */
  public function getExporting() {
    return $this->exporting;
  }

  /**
   * Set exporting options.
   *
   * @param \Drupal\charts_highcharts\Settings\Highcharts\ExportingOptions|null $exporting
   *   The exporting options.
   */
  public function setExporting($exporting = NULL) {
    $this->exporting = $exporting;
  }

  /**
   * @return mixed
   */
  public function getPane() {
    return $this->pane;
  }

  /**
   * @param mixed $pane
   */
  public function setPane($pane) {
    $this->pane = $pane;
  }

  /**
   * @return mixed
   */
  public function getSubtitle() {
    return $this->subtitle;
  }

  /**
   * @param mixed $subtitle
   */
  public function setSubtitle($subtitle) {
    $this->subtitle = $subtitle;
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
