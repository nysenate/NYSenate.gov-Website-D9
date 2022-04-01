<?php

namespace Drupal\charts_overrides\Plugin\override;

use Drupal\charts_google\Plugin\override\GoogleOverrides;

/**
 * Defines a concrete class for a Google.
 *
 * @ChartOverride(
 *   id = "charts_overrides_google",
 *   name = @Translation("Google Overrides")
 * )
 */
class ChartsOverridesGoogle extends GoogleOverrides {

  public function chartOverrides(array $originalOptions = []) {

    $options = [];

    //    The following are currently available for overriding; they are the
    //    private variables in
    //    charts_google/src/Settings/Google/GoogleOptions.php

    //    $options['title'];
    //    $options['subTitle'];
    //    $options['titlePosition'];
    //    $options['axisTitlesPosition'];
    //    $options['chartArea'];
    //    $options['hAxes'];
    //    $options['vAxes'];
    //    $options['colors'];
    //    $options['pointSize'];
    //    $options['legend'];
    //    $options['width'];
    //    $options['height'];
    //    $options['is3D'];
    //    $options['isStacked'];
    //    $options['greenTo'];
    //    $options['greenFrom'];
    //    $options['redTo'];
    //    $options['redFrom'];
    //    $options['yellowTo'];
    //    $options['yellowFrom'];
    //    $options['max'];
    //    $options['min'];
    //    $options['curveType'];

    //    An example of how to override the colors property.
    //    $options['colors'] = [
    //        '#000000',
    //        '#999999',
    //        '#666666'
    //    ];

    return $options;
  }

}
