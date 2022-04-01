<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * Chart Dimensions.
 */
class ChartDimensions implements \JsonSerializable {

  private $ratio;

  /**
   * Get Ratio.
   *
   * @return mixed
   *   Ratio.
   */
  public function getRatio() {
    return $this->ratio;
  }

  /**
   * Set Ratio.
   *
   * @param mixed $ratio
   *   Ratio.
   */
  public function setRatio($ratio) {
    $this->ratio = $ratio;
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
