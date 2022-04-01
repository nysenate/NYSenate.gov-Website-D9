<?php

namespace Drupal\charts_c3\Plugin\override;

use Drupal\charts\Plugin\override\AbstractChartOverride;

/**
 * Defines a concrete class for a C3 Charts.
 *
 * @ChartOverride(
 *   id = "c3_overrides",
 *   name = @Translation("C3 Charts Overrides")
 * )
 */
class C3Overrides extends AbstractChartOverride {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

//    // An example of how to override the color property.
//    $options['color'] = [
//      'pattern' => [
//        '#000000',
//        '#999999',
//        '#666666'
//      ]
//    ];

    return $options;
  }

}
