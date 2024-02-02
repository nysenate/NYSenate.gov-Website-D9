<?php

/**
 * @file
 * Post-update functions for the Facets module.
 */

/**
 * Add the hierarchy processor to facets that have the hierarchy enabled.
 */
function facets_post_update_8001_8001_8001_8001_8001_8001_8001_8001_add_hierarchy_processor() {
  $config_factory = \Drupal::configFactory();

  // Find all facets that have the hierarchy enabled, but do not use the
  // hierarchy processor.
  foreach ($config_factory->listAll('facets.facet.') as $facet_config_name) {
    $facet = $config_factory->getEditable($facet_config_name);
    if ($facet->get('use_hierarchy')) {
      $processor_configs = $facet->get('processor_configs');
      if (!isset($processor_configs['hierarchy_processor'])) {
        // Enable the hierarchy processor.
        $processor_configs['hierarchy_processor'] = [
          'id' => 'hierarchy_processor',
          'weights' => [
            'build' => 100,
          ],
          'settings' => [],
        ];
        $facet->set('processor_configs', $processor_configs);
        $facet->save(TRUE);
      }
    }
  }
}
