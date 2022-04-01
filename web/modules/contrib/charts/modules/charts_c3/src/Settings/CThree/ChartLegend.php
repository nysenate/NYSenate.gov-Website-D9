<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * Chart Legend.
 */
class ChartLegend implements \JsonSerializable {

  private $show;

  /**
   * Get Show.
   *
   * @return mixed
   *   Show.
   */
  public function getShow() {
    return $this->show;
  }

  /**
   * Set Show.
   *
   * @param mixed $show
   *   Show.
   */
  public function setShow($show) {
    $this->show = $show;
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
