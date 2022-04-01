<?php

namespace Drupal\charts_google\Settings\Google;

/**
 * Class GoogleOptions.
 *
 * @package Drupal\charts_google\Settings\Google
 */
class GoogleOptions implements \JsonSerializable {

  /**
   * For Material Charts, this option specifies the title.
   *
   * @var mixed
   */
  private $title;

  /**
   * For Material Charts, this option specifies the subtitle.
   *
   * @var mixed
   */
  private $subTitle;

  /**
   * Where to place the chart title, compared to the chart area.
   *
   * @var mixed
   */
  private $titlePosition;

  /**
   * Where to place the axis titles, compared to the chart area.
   *
   * @var mixed
   */
  private $axisTitlesPosition;

  /**
   * Chart Area.
   *
   * An array with members to configure the placement and size of the chart
   * area.
   *
   * @var mixed
   */
  private $chartArea;

  /**
   * Horizontal Axes.
   *
   * Specifies properties for individual horizontal axes, if the chart has
   * multiple horizontal axes.
   *
   * @var mixed
   */
  private $hAxes;

  /**
   * Vertical Axes.
   *
   * An array with members to configure various vertical axis elements.
   *
   * @var mixed
   */
  private $vAxes;

  /**
   * Colors.
   *
   * The colors to use for the chart elements. An array of strings, where each
   * element is an HTML color string.
   *
   * @var mixed
   */
  private $colors;

  /**
   * Font Size.
   *
   * The default font size, in pixels, of all text in the chart. You can
   * override this using properties for specific chart elements.
   *
   * @var int
   */
  private $fontSize;

  /**
   * Font Name.
   *
   * The default font face for all text in the chart.
   *
   * @var string
   */
  private $fontName;

  /**
   * backgroundColor.
   *
   * The color for the background of the chart.
   *
   * @var mixed
   */
  private $backgroundColor;

  /**
   * pointSize.
   *
   * The size of the data markers (points) for a line or similar chart type.
   *
   * @var int
   */
  private $pointSize;

  /**
   * Legend.
   *
   * An array with members to configure various aspects of the legend. Or string
   * for the position of the legend.
   *
   * @var mixed
   */
  private $legend;

  /**
   * Width of the chart, in pixels.
   *
   * @var mixed
   */
  private $width;

  /**
   * Height of the chart, in pixels.
   *
   * @var mixed
   */
  private $height;

  /**
   * 3D chart option.
   *
   * @var mixed
   */
  private $is3D;

  /**
   * Stacking option.
   *
   * @var mixed
   */
  private $isStacked;

  /**
   * Gauge green area max range.
   *
   * @var mixed
   */
  private $greenTo;

  /**
   * Gauge green area minimum range.
   *
   * @var mixed
   */
  private $greenFrom;

  /**
   * Gauge red area max range.
   *
   * @var mixed
   */
  private $redTo;

  /**
   * Gauge red area minimum range.
   *
   * @var mixed
   */
  private $redFrom;

  /**
   * Gauge yellow area max range.
   *
   * @var mixed
   */
  private $yellowTo;

  /**
   * Gauge yellow area minimum range.
   *
   * @var mixed
   */
  private $yellowFrom;

  /**
   * Gauge maximum value.
   *
   * @var mixed
   */
  private $max;

  /**
   * Gauge minimum value.
   *
   * @var mixed
   */
  private $min;

  /**
   * curveType value.
   *
   * @var string
   */
  private $curveType;

  /**
   * sliceVisibilityThreshold value.
   *
   * @var float
   */
  private $sliceVisibilityThreshold;

  /**
   * pieSliceText value
   *
   * @var string
   */
  private $pieSliceText;

  /**
   * Gets the title of the Material Chart. Only Material Charts support titles.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Sets the title of the Material Chart. Only Material Charts support titles.
   *
   * @param string $title
   *   Title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Get Subtitle.
   *
   * Gets the subtitle of the Material Chart. Only Material Charts support
   * subtitle.
   *
   * @return string
   *   Subtitle.
   */
  public function getSubTitle() {
    return $this->subTitle;
  }

  /**
   * Set Subtitle.
   *
   * Sets the subtitle of the Material Chart. Only Material Charts support
   * subtitle.
   *
   * @param string $title
   *   SubTitle.
   */
  public function setSubTitle($title) {
    $this->subTitle = $title;
  }

  /**
   * Gets the position of chart title.
   *
   * @return string
   *   Title Position.
   */
  public function getTitlePosition() {
    return $this->titlePosition;
  }

  /**
   * Sets the position of chart title.
   *
   * Supported values:
   * - in: Draw the title inside the chart area.
   * - out: Draw the title outside the chart area.
   * - none: Omit the title.
   *
   * @param string $position
   *   Title Position.
   */
  public function setTitlePosition($position) {
    $this->titlePosition = $position;
  }

  /**
   * Gets the position of the axis titles.
   *
   * @return string
   *   Axis Titles Position.
   */
  public function getAxisTitlesPosition() {
    return $this->axisTitlesPosition;
  }

  /**
   * Sets the position of the axis titles.
   *
   * Supported values:
   * - in: Draw the axis titles inside the chart area.
   * - out: Draw the axis titles outside the chart area.
   * - none: Omit the axis titles.
   *
   * @param string $position
   *   Position.
   */
  public function setAxisTitlesPosition($position) {
    $this->axisTitlesPosition = $position;
  }

  /**
   * Gets the chartArea property.
   *
   * @return mixed
   *   Chart Area.
   */
  public function getChartArea() {
    return $this->chartArea;
  }

  /**
   * Sets the chartArea property.
   *
   * @param mixed $chartArea
   *   Chart Area.
   */
  public function setChartArea($chartArea) {
    $this->chartArea = $chartArea;
  }

  /**
   * Gets the horizontal axes.
   *
   * @return array
   *   Horizontal Axes.
   */
  public function getHorizontalAxes() {
    return $this->hAxes;
  }

  /**
   * Sets the horizontal axes.
   *
   * @param array $hAxes
   *   Horizontal axes.
   */
  public function setHorizontalAxes(array $hAxes = []) {
    $this->hAxes = $hAxes;
  }

  /**
   * Gets the vertical axes.
   *
   * @return array
   *   Vertical axes.
   */
  public function getVerticalAxes() {
    return $this->vAxes;
  }

  /**
   * Sets the vertical axes.
   *
   * @param array $vAxes
   *   Vertical axes.
   */
  public function setVerticalAxes(array $vAxes = []) {
    $this->vAxes = $vAxes;
  }

  /**
   * Get Colors.
   *
   * Gets the colors to use for the chart elements. An array of strings, where
   * each element is an HTML color string.
   *
   * @return array
   *   Colors.
   */
  public function getColors() {
    return $this->colors;
  }

  /**
   * Gets the default font size for all text in the chart.
   *
   * @return int
   *   The font size, in pixels.
   */
  public function getFontSize() {
    return $this->fontSize;
  }

  /**
   * Sets the default font size for all text in the chart.
   *
   * @param int $fontSize
   *   The font size, in pixels.
   */
  public function setFontSize($fontSize = NULL) {
    $this->fontSize = $fontSize;
  }

  /**
   * Set Colors.
   *
   * Sets the colors to use for the chart elements. An array of strings, where
   * each element is an HTML color string.
   *
   * @param array $colors
   *   Colors.
   */
  public function setColors(array $colors = []) {
    $this->colors = $colors;
  }

  /**
   * Get Font Name.
   *
   * Gets the default font face for all text in the chart.
   *
   * @return string
   *   Font Name.
   */
  public function getFontName() {
    return $this->fontName;
  }

  /**
   * Set Font Name.
   *
   * Sets the default font face for all text in the chart.
   *
   * @param string $fontName
   *   Font Name.
   */
  public function setFontName($fontName = NULL) {
    $this->fontName = $fontName;
  }

  /**
   * @return mixed
   */
  public function getBackgroundColor() {
    return $this->backgroundColor;
  }

  /**
   * @param mixed $backgroundColor
   */
  public function setBackgroundColor($backgroundColor) {
    $this->backgroundColor = $backgroundColor;
  }

  /**
   * Gets the Legend properties.
   *
   * @return mixed
   *   Legend.
   */
  public function getLegend() {
    return $this->legend;
  }

  /**
   * Sets the Legend properties.
   *
   * @param mixed $legend
   *   Legend.
   */
  public function setLegend($legend) {
    $this->legend = $legend;
  }

  /**
   * Gets a Legend property.
   *
   * @param mixed $key
   *   Property key.
   *
   * @return mixed
   *   Legend Property.
   */
  public function getLegendProperty($key) {
    return isset($this->legend[$key]) ? $this->legend[$key] : NULL;
  }

  /**
   * Sets a Legend property.
   *
   * @param mixed $key
   *   Property key.
   * @param mixed $value
   *   Property value.
   */
  public function setLegendProperty($key, $value) {
    $this->legend[$key] = $value;
  }

  /**
   * Gets the width of the chart.
   *
   * @return mixed
   *   Width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Sets the width of the chart.
   *
   * @param mixed $width
   *   Width of the chart, in pixels.
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  /**
   * Gets the height of the chart.
   *
   * @return mixed
   *   Height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Sets the height of the chart.
   *
   * @param mixed $height
   *   Height of the chart, in pixels.
   */
  public function setHeight($height) {
    $this->height = $height;
  }

  /**
   * Gets three-dimensional chart option.
   *
   * @return mixed
   *   3D option.
   */
  public function getThreeDimensional() {
    return $this->is3D;
  }

  /**
   * Sets three-dimensional chart option.
   *
   * @param mixed $threeDimensional
   *   3D option.
   */
  public function setThreeDimensional($is3D) {
    $this->is3D = $is3D;
  }

  /**
   * Gets stacking chart option.
   *
   * @return mixed
   *   Stacking option.
   */
  public function getStacking() {
    return $this->isStacked;
  }

  /**
   * Sets stacking chart option.
   *
   * @param mixed $isStacked
   *   Stacking option.
   */
  public function setStacking($isStacked) {
    $this->isStacked = $isStacked;
  }

  /**
   * @param mixed $greenTo
   */
  public function setGreenTo($greenTo) {
    $this->greenTo = $greenTo;
  }

  /**
   * @param mixed $greenFrom
   */
  public function setGreenFrom($greenFrom) {
    $this->greenFrom = $greenFrom;
  }

  /**
   * @param mixed $redTo
   */
  public function setRedTo($redTo) {
    $this->redTo = $redTo;
  }

  /**
   * @param mixed $redFrom
   */
  public function setRedFrom($redFrom) {
    $this->redFrom = $redFrom;
  }

  /**
   * @param mixed $yellowTo
   */
  public function setYellowTo($yellowTo) {
    $this->yellowTo = $yellowTo;
  }

  /**
   * @param mixed $yellowFrom
   */
  public function setYellowFrom($yellowFrom) {
    $this->yellowFrom = $yellowFrom;
  }

  /**
   * @param mixed $max
   */
  public function setMax($max) {
    $this->max = $max;
  }

  /**
   * @param mixed $min
   */
  public function setMin($min) {
    $this->min = $min;
  }

  /**
   * Gets the curveType properties.
   *
   * @return string
   *   curveType.
   */
  public function getCurveType() {
    return $this->curveType;
  }

  /**
   * @param string $curveType
   */
  public function setCurveType($curveType) {
    $this->curveType = $curveType;
  }

  /**
   * Gets the colorAxis properties.
   *
   * @return string
   *   colorAxis.
   */
  public function getColorAxis() {
    return $this->colorAxis;
  }

  /**
    * @param string $colorAxis
   */
  public function setColorAxis(array $colorAxis = []) {
    $this->colorAxis = $colorAxis;
  }

/**
 * Gets the datalessRegionColor properties.
 *
 * @return string
 *   datalessRegionColor.
 */
  public function getDatalessRegionColor() {
    return $this->datalessRegionColor;
  }

  /**
    * @param string $datalessRegionColor
   */
  public function setDatalessRegionColor($datalessRegionColor) {
    $this->datalessRegionColor = $datalessRegionColor;
  }

  /**
   * @return int
   */
  public function getPointSize() {
    return $this->pointSize;
  }

  /**
   * @param int $pointSize
   */
  public function setPointSize($pointSize) {
    $this->pointSize = $pointSize;
  }

  /**
   * @return float
   */
  public function getSliceVisibilityThreshold() {
    return $this->sliceVisibilityThreshold;
  }

  /**
   * @param float $sliceVisibilityThreshold
   */
  public function setSliceVisibilityThreshold(float $sliceVisibilityThreshold) {
    $this->sliceVisibilityThreshold = $sliceVisibilityThreshold;
  }

  /**
   * @return string
   */
  public function getPieSliceText() {
    return $this->pieSliceText;
  }

  /**
   * @param string $pieSliceText
   */
  public function setPieSliceText(string $pieSliceText) {
    $this->pieSliceText = $pieSliceText;
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
