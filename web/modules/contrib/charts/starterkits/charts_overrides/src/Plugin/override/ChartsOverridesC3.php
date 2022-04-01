<?php

namespace Drupal\charts_overrides\Plugin\override;

use Drupal\charts_c3\Plugin\override\C3Overrides;

/**
 * Defines a concrete class for a C3.
 *
 * @ChartOverride(
 *   id = "charts_overrides_c3",
 *   name = @Translation("C3 Overrides")
 * )
 */
class ChartsOverridesC3 extends C3Overrides {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

    //    The following are currently available for overriding; they are the
    //    private variables in charts_c3/src/Settings/CThree/CThree.php
    //
    //    $options['color'];
    //    $options['bindto'];
    //    $options['data'];
    //    $options['axis'];
    //    $options['title'];
    //    $options['gauge'];
    //    $options['point'];
    //
    //    An example of how to override the color property.
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
