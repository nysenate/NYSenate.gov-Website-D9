<?php

namespace Drupal\charts_overrides\Plugin\override;

use Drupal\charts_billboard\Plugin\override\BillboardOverrides;

/**
 * Defines a concrete class for a Billboard.
 *
 * @ChartOverride(
 *   id = "charts_overrides_billboard",
 *   name = @Translation("Billboard.js Overrides")
 * )
 */
class ChartsOverridesBillboard extends BillboardOverrides {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

    //    The following are currently available for overriding; they are the
    //    private variables in
    //    charts_billboard/src/Settings/Billboard/BillboardChart.php
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
