<?php

namespace Drupal\charts_google\Plugin\override;

use Drupal\charts\Plugin\override\AbstractChartOverride;

/**
 * Defines a concrete class for a Google Charts.
 *
 * @ChartOverride(
 *   id = "google_overrides",
 *   name = @Translation("Google Charts Overrides")
 * )
 */
class GoogleOverrides extends AbstractChartOverride {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

//    // An example of how to override ....
//    $options['colors'] = [
//        '#000000',
//        '#999999',
//        '#666666'
//    ];

    return $options;
  }

}
