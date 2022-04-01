<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Plot Options.
 */
class PlotOptions implements \JsonSerializable {

  /**
   * Get Plot Options.
   *
   * @return mixed
   *   Plot Options.
   */
  public function getPlotOptions() {
    return $this->{$type};
  }

  /**
   * Set Plot Options.
   *
   * @param mixed $plotOptions
   *   Plot Options.
   */
  public function setPlotOptions($typeOptions, $plotOptions) {
    $type = $typeOptions;
    $this->{$type} = $plotOptions;
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
