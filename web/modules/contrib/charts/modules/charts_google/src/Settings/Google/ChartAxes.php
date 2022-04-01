<?php

namespace Drupal\charts_google\Settings\Google;

/**
 * Class ChartAxes.
 *
 * @package Drupal\charts_google\Settings\Google
 *
 *  Chart Axis options are described here:
 * @see https://developers.google.com/chart/interactive/docs/gallery/columnchart#configuration-options
 */
class ChartAxes implements \JsonSerializable {

  /**
   * Chart Axis property that specifies a title for the axis.
   *
   * @var mixed
   */
  private $title;

  /**
   * An array that specifies the axis title text style.
   *
   * @var mixed
   */
  private $titleTextStyle;

  /**
   * Baseline.
   *
   * Chart Axis property that specifies the baseline for the axis. If the
   * baseline is larger than the highest grid line or smaller than the lowest
   * grid line, it will be rounded to the closest gridline.
   *
   * @var mixed
   */
  private $baseline;

  /**
   * Baseline Color.
   *
   * Specifies the color of the baseline for the axis. Can be any HTML
   * color string, for example: 'red' or '#00cc00'.
   *
   * @var mixed
   */
  private $baselineColor;

  /**
   * Direction.
   *
   * The direction in which the values along the axis grow. Specify -1
   * to reverse the order of the values.
   *
   * @var mixed
   */
  private $direction;

  /**
   * A format string for numeric axis labels.
   *
   * @var mixed
   */
  private $format;

  /**
   * Text Position.
   *
   * Position of the axis text, relative to the chart area.
   * Supported values: 'out', 'in', 'none'.
   *
   * @var mixed
   */
  private $textPosition;

  /**
   * An array that specifies the axis text style.
   *
   * @var mixed
   */
  private $textStyle;

  /**
   * Max Value.
   *
   * Moves the max value of the axis to the specified value; this will
   * be upward in most charts. Ignored if this is set to a value smaller than
   * the maximum y-value of the data.
   *
   * @var mixed
   */
  private $maxValue;

  /**
   * Min Value.
   *
   * Moves the min value of the axis to the specified value; this will
   * be downward in most charts. Ignored if this is set to a value greater than
   * the minimum y-value of the data.
   *
   * @var mixed
   */
  private $minValue = 0;

  /**
   * View Window Mode.
   *
   * Specifies how to scale the axis to render the values within the
   * chart area.
   *
   * @var mixed
   */
  private $viewWindowMode;

  /**
   * Specifies the cropping range of the axis.
   *
   * @var mixed
   */
  private $viewWindow;

  /**
   * Get Chart Axis property that specifies a title for the axis.
   *
   * @return mixed
   *   Title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Set Chart Axis property that specifies a title for the axis.
   *
   * @param mixed $value
   *   Title.
   */
  public function setTitle($value) {
    $this->title = $value;
  }

  /**
   * Get an array that specifies the axis title text style.
   *
   * @return mixed
   *   Title Text Style.
   */
  public function getTitleTextStyle() {
    return $this->titleTextStyle;
  }

  /**
   * Set an array that specifies the axis title text style.
   *
   * @param mixed $value
   *   Title Text Style.
   */
  public function setTitleTextStyle($value) {
    $this->titleTextStyle = $value;
  }

  /**
   * Get an array property that specifies the axis title text style.
   *
   * @param string $key
   *   Machine name of the text style property.
   *
   * @return mixed
   *   Title Text Style Value.
   */
  public function getTitleTextStyleValue($key) {
    return isset($this->titleTextStyle[$key]) ? $this->titleTextStyle[$key] : NULL;
  }

  /**
   * Set an array property that specifies the axis title text style.
   *
   * @param string $key
   *   Machine name of the text style property.
   * @param mixed $value
   *   Value of the text style property.
   */
  public function setTitleTextStyleValue($key, $value) {
    $this->titleTextStyle[$key] = $value;
  }

  /**
   * Get Chart Axis property that specifies the baseline for the axis.
   *
   * @return mixed
   *   Baseline.
   */
  public function getBaseline() {
    return $this->baseline;
  }

  /**
   * Set Chart Axis property that specifies the baseline for the axis.
   *
   * @param mixed $value
   *   Baseline.
   */
  public function setBaseline($value) {
    $this->baseline = $value;
  }

  /**
   * Get the color of the baseline for the axis.
   *
   * @return mixed
   *   Baseline Color.
   */
  public function getBaselineColor() {
    return $this->baselineColor;
  }

  /**
   * Set the color of the baseline for the axis.
   *
   * @param mixed $value
   *   Baseline Color.
   */
  public function setBaselineColor($value) {
    $this->baselineColor = $value;
  }

  /**
   * Get the direction in which the values along the axis grow.
   *
   * @return mixed
   *   Direction.
   */
  public function getDirection() {
    return $this->direction;
  }

  /**
   * Set the direction in which the values along the axis grow.
   *
   * @param mixed $value
   *   Direction.
   */
  public function setDirection($value) {
    $this->direction = $value;
  }

  /**
   * Get the format string for numeric axis labels.
   *
   * @return mixed
   *   Format.
   */
  public function getFormat() {
    return $this->format;
  }

  /**
   * Set a format string for numeric axis labels.
   *
   * @param mixed $value
   *   Format.
   */
  public function setFormat($value) {
    $this->format = $value;
  }

  /**
   * Get the position of the axis text, relative to the chart area.
   *
   * @return mixed
   *   Text Position.
   */
  public function getTextPosition() {
    return $this->textPosition;
  }

  /**
   * Set the position of the axis text, relative to the chart area.
   *
   * @param mixed $value
   *   Text Position.
   */
  public function setTextPosition($value) {
    $this->textPosition = $value;
  }

  /**
   * Get an array that specifies the axis text style.
   *
   * @return mixed
   *   Text Style.
   */
  public function getTextStyle() {
    return $this->textStyle;
  }

  /**
   * Set an array that specifies the axis text style.
   *
   * @param mixed $value
   *   Text Style.
   */
  public function setTextStyle($value) {
    $this->textStyle = $value;
  }

  /**
   * Get an array property that specifies the axis text style.
   *
   * @param string $key
   *   Machine name of the text style property.
   *
   * @return mixed
   *   Text Style Value.
   */
  public function getTextStyleValue($key) {
    return isset($this->textStyle[$key]) ? $this->textStyle[$key] : NULL;
  }

  /**
   * Set an array property that specifies the axis text style.
   *
   * @param string $key
   *   Machine name of the text style property.
   * @param mixed $value
   *   Value of the text style property.
   */
  public function setTextStyleValue($key, $value) {
    $this->textStyle[$key] = $value;
  }

  /**
   * Get the max value of the axis.
   *
   * @return mixed
   *   Max Value.
   */
  public function getMaxValue() {
    return $this->maxValue;
  }

  /**
   * Set the max value of the axis.
   *
   * @param mixed $value
   *   Max Value.
   */
  public function setMaxValue($value) {
    $this->maxValue = $value;
  }

  /**
   * Get the min value of the axis.
   *
   * @return mixed
   *   Min Value.
   */
  public function getMinValue() {
    return $this->minValue;
  }

  /**
   * Set the min value of the axis.
   *
   * @param mixed $value
   *   Min Value.
   */
  public function setMinValue($value) {
    $this->minValue = $value;
  }

  /**
   * Get View Window Mode.
   *
   * Get the value that specifies how to scale the axis to render the
   * values within the chart area.
   *
   * @return mixed
   *   View Window Mode.
   */
  public function getViewWindowMode() {
    return $this->viewWindowMode;
  }

  /**
   * Set View Window Mode.
   *
   * Set the value that specifies how to scale the axis to render the
   * values within the chart area.
   *
   * @param mixed $value
   *   View Window Mode.
   */
  public function setViewWindowMode($value) {
    $this->viewWindowMode = $value;
  }

  /**
   * Get an array that specifies the cropping range of the axis.
   *
   * @return mixed
   *   View Window.
   */
  public function getViewWindow() {
    return $this->viewWindow;
  }

  /**
   * Set an array that specifies the cropping range of the axis.
   *
   * @param mixed $value
   *   View Window.
   */
  public function setViewWindow($value) {
    $this->viewWindow = $value;
  }

  /**
   * Get View Window Value.
   *
   * Get an array property that specifies the the cropping range of the
   * axis.
   *
   * @param string $key
   *   Property key.
   *
   * @return mixed
   *   View Window Value.
   */
  public function getViewWindowValue($key) {
    return isset($this->viewWindow[$key]) ? $this->viewWindow[$key] : NULL;
  }

  /**
   * Set View Window Value.
   *
   * Set an array property that specifies the cropping range of the horizontal
   * axis.
   *
   * @param string $key
   *   Property key.
   * @param mixed $value
   *   Property value.
   */
  public function setViewWindowValue($key, $value) {
    $this->viewWindow[$key] = $value;
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
