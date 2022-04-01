<?php

namespace Drupal\charts_billboard\Settings\Billboard;

/**
 * Chart Axis.
 */
class ChartAxis implements \JsonSerializable {

  private $rotated = FALSE;

  private $x = ['type' => 'category'];

  /**
   * Get Rotated.
   *
   * @return mixed
   *   Rotated.
   */
  public function getRotated() {
    return $this->rotated;
  }

  /**
   * Set Rotated.
   *
   * @param mixed $rotated
   *   Rotated.
   */
  public function setRotated($rotated) {
    $this->rotated = $rotated;
  }

  /**
   * Get X.
   *
   * @return mixed $x
   *   Data for X Axis.
   */
  public function getX() {
    return $this->x;
  }

  /**
   * Set X.
   *
   * @param mixed $x
   *   Data for X Axis.
   */
  public function setX($x) {
      $this->x = $x;
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
