<?php

namespace Drupal\charts_overrides\Plugin\override;

use Drupal\charts_chartjs\Plugin\override\ChartjsOverrides;

/**
 * Defines a concrete class for a Billboard.
 *
 * @ChartOverride(
 *   id = "charts_overrides_chartjs",
 *   name = @Translation("Chart.js Overrides")
 * )
 */
class ChartsOverridesChartjs extends ChartjsOverrides {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

    //    The following are currently available for overriding; they are the
    //    private variables in
    //    charts_chartjs/src/Settings/Chartjs/ChartjsChart.php
    //
    //    An example of how to override the field_colors property.
    //    $options['field_colors'] = [
    //      'field_one': '#2f7ed8',
    //      'field_two': '#8bbc21'
    //    ];

    return $options;
  }

}
