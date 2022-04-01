<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsChart.
 *
 * @package Drupal\charts_google\Settings\Google
 */
class ChartjsChart implements \JsonSerializable {

  /**
   * For Chart.js Charts, this option specifies the type.
   *
   * @var mixed
   */
  private $type;

  /**
   * For Chart.js Charts, this option specifies the data object.
   *
   * @var mixed
   */
  private $data;

  /**
   * For Chart.js Charts, this option specifies the options object.
   *
   * @var mixed
   */
  private $options;

  /**
   * Scale color ranges.
   *
   * @var boolean
   */
  private $scaleColorRanges;

  /**
   * For gauge range.
   *
   * @var mixed
   */
  private $range;

  /**
   * Get Type.
   *
   * @return mixed
   *   Type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set Type.
   *
   * @param mixed $type
   *   Type.
   */
  public function setType($type) {
    $this->type = $type;
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
   * Get Options.
   *
   * @return mixed
   *   Options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Set Options.
   *
   * @param mixed $options
   *   Options.
   */
  public function setOptions($options) {
    $this->options = $options;
  }

  /**
   * Gets stacking chart option.
   *
   * @return mixed
   *   Scale color ranges.
   */
  public function getScaleColorRanges() {
    return $this->scaleColorRanges;
  }

  /**
   * Sets scale color options.
   *
   * @param mixed $scaleColorRanges
   *   Scale color options.
   */
  public function setScaleColorRanges($scaleColorRanges) {
    $this->scaleColorRanges = $scaleColorRanges;
  }

  /**
   * @return mixed
   */
  public function getRange() {
    return $this->range;
  }

  /**
   * @param mixed $range
   */
  public function setRange($range) {
    $this->range = $range;
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
