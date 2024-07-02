<?php

/**
 * @file
 * Display a list of available key providers.
 */

/**
 * Get a list of available key providers.
 */
function drush_key_provider_list() {
  $result = [];

  $tags = explode(',', (string) drush_get_option('tags'));
  if ($storage_method = drush_get_option('storage-method')) {
    @trigger_error("The Drush --storage-method option is deprecated in key:1.18.0 and is removed from key:2.0.0. Use the --tags option instead. See https://www.drupal.org/node/3364701", E_USER_DEPRECATED);
    \Drupal::logger('key')->log('warning', (dt("The Drush --storage-method option is deprecated in key:1.18.0 and is removed from key:2.0.0. Use the --tags option instead. See https://www.drupal.org/node/3364701", E_USER_DEPRECATED)));
    $tags[] = $storage_method;
    $tags = array_unique($tags);
  }

  $plugins = \Drupal::service('plugin.manager.key.key_provider')->getDefinitions();
  foreach ($plugins as $id => $plugin) {
    if (!$tags || array_intersect($plugin['tags'], $tags)) {
      $row = [];
      $row['id'] = $id;
      $row['description'] = $plugin['description'];

      $result[$id] = $row;
    }
  }

  return $result;
}
