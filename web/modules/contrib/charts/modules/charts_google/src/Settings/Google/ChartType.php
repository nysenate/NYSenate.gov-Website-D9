<?php

namespace Drupal\charts_google\Settings\Google;

/**
 * Chart Type.
 */
class ChartType implements \JsonSerializable {

  private $type;

  /**
   * Get Chart Type.
   *
   * @return mixed
   *   Chart Type.
   */
  public function getChartType() {
    return $this->type;
  }

  /**
   * Chart Type.
   *
   * @param mixed $type
   *   Chart Type.
   */
  public function setChartType($type) {
    $ucType = ucfirst($type);
    $this->type = $ucType . 'Chart';
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
