<?php

namespace Drupal\charts_billboard\Settings\Billboard;

/**
 * Points.
 */
class ChartPoints implements \JsonSerializable {

  private $show;

  /**
   * @return bool
   */
  public function getShow() {
    return $this->show;
  }

  /**
   * @param bool $show
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
