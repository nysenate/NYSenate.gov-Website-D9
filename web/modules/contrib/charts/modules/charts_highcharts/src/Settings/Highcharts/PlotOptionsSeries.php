<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Plot Options Series.
 */
class PlotOptionsSeries implements \JsonSerializable {

  private $dataLabels;

  private $depth = 35;

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
   * @return int
   */
  public function getDepth() {
    return $this->depth;
  }

  /**
   * @param int $depth
   */
  public function setDepth($depth) {
    $this->depth = $depth;
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
