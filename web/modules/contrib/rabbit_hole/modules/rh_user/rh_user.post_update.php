<?php

/**
 * @file
 * Post update functions for "Rabbit Hole users" module.
 */

/**
 * Add entity_type_id key to Rabbit hole user settings.
 */
function rh_user_post_update_resave_user_settings() {
  /** @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface $settings_manager */
  $settings_manager = \Drupal::service('rabbit_hole.behavior_settings_manager');
  $settings_manager->saveBehaviorSettings([], 'user');
}
