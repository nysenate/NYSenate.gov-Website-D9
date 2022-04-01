<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Tooltip.
 */
class Tooltip implements \JsonSerializable {

  private $valueSuffix = '';

  private $enabled = FALSE;

  /**
   * Get Value Suffix.
   *
   * @return string
   *   Value Suffix.
   */
  public function getValueSuffix() {
    return $this->valueSuffix;
  }

  /**
   * Set Value Suffix.
   *
   * @param string $valueSuffix
   *   Value Suffix.
   */
  public function setValueSuffix($valueSuffix) {
    $this->valueSuffix = $valueSuffix;
  }

  /**
   * Get Enabled.
   *
   * @return bool
   *   Enabled.
   */
  public function getEnabled() {
    return $this->enabled;
  }

  /**
   * Set Enabled.
   *
   * @param bool $enabled
   *   Enabled.
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
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
