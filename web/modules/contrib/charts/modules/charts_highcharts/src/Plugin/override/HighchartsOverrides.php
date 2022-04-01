<?php

namespace Drupal\charts_highcharts\Plugin\override;

use Drupal\charts\Plugin\override\AbstractChartOverride;

/**
 * Defines a concrete class for a Highcharts.
 *
 * @ChartOverride(
 *   id = "highcharts_overrides",
 *   name = @Translation("Highcharts Overrides")
 * )
 */
class HighchartsOverrides extends AbstractChartOverride {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

//    // An example of how to override plotOptions property.
//    $options['plotOptions'] = [
//        'series' => [
//            'dataLabels' => [
//                'enabled' => true,
//                'color' => 'red'
//            ],
//        ],
//    ];

    return $options;
  }

}
