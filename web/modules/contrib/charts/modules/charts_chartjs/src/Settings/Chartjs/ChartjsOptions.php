<?php

namespace Drupal\charts_chartjs\Settings\Chartjs;

/**
 * Class ChartjsOptions.
 *
 * @package Drupal\charts_chartjs\Settings\Chartjs
 */
class ChartjsOptions implements \JsonSerializable {

  /**
   * Scales object.
   *
   * @var mixed
   */
  private $scales;

  /**
   * Tooltops object.
   *
   * @var mixed
   */
  private $tooltips;

  /**
   * Legend object.
   *
   * @var mixed
   */
  private $legend;

  /**
   * Title object.
   *
   * @var mixed
   */
  private $title;

  /**
   * Gets scales object.
   *
   * @return mixed
   *   Scales object.
   */
  public function getScales() {
    return $this->scales;
  }

  /**
   * Sets stacking chart option.
   *
   * @param mixed $scales
   *   Scales option.
   */
  public function setScales($scales) {
    $this->scales = $scales;
  }

  /**
   * @return mixed
   */
  public function getTooltips() {
    return $this->tooltips;
  }

  /**
   * @param mixed $tooltips
   */
  public function setTooltips($tooltips) {
    $this->tooltips = $tooltips;
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
   * @return mixed
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param mixed $title
   */
  public function setTitle($title) {
    $this->title = $title;
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
