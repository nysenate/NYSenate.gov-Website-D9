<?php

namespace Drupal\charts_chartjs\Plugin\override;

use Drupal\charts\Plugin\override\AbstractChartOverride;

/**
 * Defines a concrete class for a Chart.js Charts.
 *
 * @ChartOverride(
 *   id = "chartjs_overrides",
 *   name = @Translation("Chart.js Charts Overrides")
 * )
 */
class ChartjsOverrides extends AbstractChartOverride {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

//    // An example of how to override ....


    return $options;
  }

}
