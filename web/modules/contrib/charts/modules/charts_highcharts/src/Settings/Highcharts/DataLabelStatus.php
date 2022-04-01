<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Data Label Status.
 */
class DataLabelStatus implements \JsonSerializable {

  private $enabled = TRUE;

  /**
   * Is Enabled.
   *
   * @return bool
   *   Enabled.
   */
  public function isEnabled() {
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
