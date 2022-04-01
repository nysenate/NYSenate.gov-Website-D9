<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Y Axis Label.
 */
class YaxisLabel implements \JsonSerializable {

  private $overflow = 'justify';
  private $suffix = '';
  private $prefix = '';

  /**
   * Set Overflow.
   *
   * @param mixed $overflow
   *   Overflow.
   */
  public function setOverflow($overflow) {
    $this->overflow = $overflow;
  }

  /**
   * Get Overflow.
   *
   * @return string
   *   Overflow.
   */
  public function getOverflow() {
    return $this->overflow;
  }

  /**
   * @return string
   */
  public function getYaxisLabelSuffix() {
    return $this->suffix;
  }

  /**
   * @param string $suffix;
   */
  public function setYaxisLabelSuffix($suffix) {
    $this->suffix = $suffix;
  }

  /**
   * @return string
   */
  public function getYaxisLabelPrefix() {
    return $this->prefix;
  }

  /**
   * @param string $prefix
   */
  public function setYaxisLabelPrefix($prefix) {
    $this->prefix = $prefix;
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
