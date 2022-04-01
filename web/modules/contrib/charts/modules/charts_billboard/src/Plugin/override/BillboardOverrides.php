<?php

namespace Drupal\charts_billboard\Plugin\override;

use Drupal\charts\Plugin\override\AbstractChartOverride;

/**
 * Defines a concrete class for a Billboard.js Charts.
 *
 * @ChartOverride(
 *   id = "billboard_overrides",
 *   name = @Translation("Billboard.js Charts Overrides")
 * )
 */
class BillboardOverrides extends AbstractChartOverride {

  /**
   * @param array $originalOptions
   *   The original options.
   *
   * @return array $options
   *   The overridden options.
   */
  public function chartOverrides(array $originalOptions = []) {

    $options = [];

    /**
     *    // An example of how to override the color property.
     *    $options['color'] = [
     *      'pattern' => [
     *        '#000000',
     *        '#999999',
     *        '#666666'
     *      ]
     *    ];
     */

    return $options;
  }

}
