<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;


class ThreeDimensionalOptions implements \JsonSerializable {

  private $enabled = TRUE;

  private $alpha = 0;

  private $beta = 0;

  private $viewDistance = 0;

  /**
   * Get enabled value.
   *
   * @return boolean
   *   The enabled value.
   */
  public function getEnabled() {
    return $this->enabled;
  }

  /**
   * Set enabled value.
   *
   * @param integer
   *   The enabled value.
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
  }

  /**
   * Get alpha value.
   *
   * @return integer
   *   The alpha value.
   */
  public function getAlpha() {
    return $this->alpha;
  }

  /**
   * Set alpha value.
   *
   * @param integer
   *   The alpha value.
   */
  public function setAlpha($alpha) {
    $this->alpha = $alpha;
  }

  /**
   * Get beta value.
   *
   * @return integer
   *   The beta value.
   */
  public function getBeta() {
    return $this->beta;
  }

  /**
   * Set beta value.
   *
   * @param integer
   *   The beta value.
   */
  public function setBeta($beta) {
    $this->beta = $beta;
  }

  /**
   * @return int
   */
  public function getViewDistance() {
    return $this->viewDistance;
  }

  /**
   * @param int $viewDistance
   */
  public function setViewDistance($viewDistance) {
    $this->viewDistance = $viewDistance;
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
