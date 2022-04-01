# Getting Started with Charts Overrides

This module is a template for custom overrides to the charts produced by the Charts module. 

## Instructions:

1. Copy the charts_overrides directory from starterkits to modules/custom/charts_overrides
2. Rename charts_overrides.info.starterkit to charts_overrides.info.yml
3. Enable Charts Overrides
4. Edit the override pugins
5. Rebuild caches.

## Considerations

If you apply an override, it will by default apply globally for that library. A better 
approach may be to use a route-based if statement, such as:

    if (\Drupal::request()->getRequestUri() == '/node/2') {
      $options['colors'] = ['#000000', '#999999', '#666666'];
    }
    
Please also be sure that anyone else developing on your site knows this module is being
used to override configuration.