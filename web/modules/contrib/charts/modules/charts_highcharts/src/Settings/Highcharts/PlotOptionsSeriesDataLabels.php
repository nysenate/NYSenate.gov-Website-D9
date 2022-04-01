<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Plot Options Series Data Labels.
 */
class PlotOptionsSeriesDataLabels implements \JsonSerializable {

  private $enabled = FALSE;

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
