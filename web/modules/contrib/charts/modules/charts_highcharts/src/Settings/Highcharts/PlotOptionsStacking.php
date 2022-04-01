<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Plot Options Series.
 */
class PlotOptionsStacking implements \JsonSerializable {

  private $dataLabels;

  private $stacking = 'normal';

  private $marker = '';

  /**
   * Get Data Labels.
   *
   * @return mixed
   *   Data Labels.
   */
  public function getDataLabels() {
    return $this->dataLabels;
  }

  /**
   * Set Data Labels.
   *
   * @param mixed $dataLabels
   *   Data Labels.
   */
  public function setDataLabels($dataLabels) {
    $this->dataLabels = $dataLabels;
  }

  /**
   * Get Stacking.
   *
   * @return mixed
   *   Stacking.
   */
  public function getStacking() {
    return $this->stacking;
  }

  /**
   * Set Stacking.
   *
   * @param mixed $stacking
   *   Stacking.
   */
  public function setStacking($stacking) {
    $this->stacking = $stacking;
  }

  /**
   * @return mixed
   */
  public function getMarker() {
    return $this->marker;
  }

  /**
   * @param mixed $marker
   */
  public function setMarker($marker) {
    $this->marker = $marker;
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
