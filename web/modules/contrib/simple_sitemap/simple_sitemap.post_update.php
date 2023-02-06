<?php

use Symfony\Component\Yaml\Yaml;

/**
 * Prevent config:import from deleting and recreating configs created through update hooks.
 */
function simple_sitemap_post_update_8403(&$sandbox) {
  // See https://www.drupal.org/project/simple_sitemap/issues/3236623.

  $config_factory = \Drupal::configFactory();
  $settings = Drupal::service('settings');

  foreach (['simple_sitemap.sitemap.', 'simple_sitemap.type.'] as $config_prefix) {
    foreach ($config_factory->listAll($config_prefix) as $config) {
      $data = Yaml::parse(@file_get_contents($settings->get('config_sync_directory') . '/' . $config . '.yml'));
      if ($data && $data['uuid']) {
        $config_factory->getEditable($config)->set('uuid', $data['uuid'])->save(TRUE);
      }
    }
  }
}
