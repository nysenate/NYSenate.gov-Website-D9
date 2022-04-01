<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * Chart Type.
 */
class ChartType implements \JsonSerializable {

  private $type;

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
