<?php

namespace Drupal\charts_overrides\Plugin\override;

use Drupal\charts_highcharts\Plugin\override\HighchartsOverrides;

/**
 * Defines a concrete class for a Highcharts.
 *
 * @ChartOverride(
 *   id = "charts_overrides_highcharts",
 *   name = @Translation("Highcharts Overrides")
 * )
 */
class ChartsOverridesHighcharts extends HighchartsOverrides {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

    //    The following are currently available for overriding; they are the
    //    private variables in
    //    charts_highcharts/src/Settings/Highcharts/HighchartsOptions.php
    //
    //    $options['plotOptions'] = [
    //        'series' => [
    //            'dataLabels' => [
    //                'enabled' => true,
    //                'color' => 'red'
    //            ],
    //        ],
    //    ];
    //
    //
    //  Unfortunately, if you want to override an axis, you have to do it in
    //  a counterintuitive way:
    //
    //  $options['axisY'] = [
    //    [
    //      'title' => [
    //        'text' => 'My Overridden Chart Title',
    //      ],
    //      'labels' => [
    //          'overflow' => 'justify',
    //          'suffix' => '',
    //          'prefix' => '',
    //      ],
    //      'stackLabels' => [
    //        'enabled' => TRUE,
    //      ],
    //      'plotBands' => NULL,
    //      'min' => NULL,
    //      'max' => NULL,
    //    ]
    //  ];
    //
    //  @todo: implement a solution from
    //    https://www.drupal.org/project/charts/issues/3049960
    //

    return $options;
  }

}
