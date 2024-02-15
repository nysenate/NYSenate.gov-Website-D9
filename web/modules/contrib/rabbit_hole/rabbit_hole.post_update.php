<?php

/**
 * @file
 * Post update functions for Rabbit hole.
 */

/**
 * Add entity_type_id and entity_id to existing rabbit_hole behavior_settings.
 */
function rabbit_hole_post_update_entity_type_id_and_entity_id_for_existing_behavior_settings() {
  $excluded_behavior_settings = [
    'default',
    'default_bundle',
  ];

  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\rabbit_hole\BehaviorSettingsInterface[] $behavior_settings */
  $behavior_settings = $entity_type_manager->getStorage('behavior_settings')
    ->loadMultiple();
  foreach ($behavior_settings as $behavior_setting) {
    // Skip the excluded behavior settings.
    if (in_array($behavior_setting->id(), $excluded_behavior_settings, TRUE)) {
      continue;
    }

    // Try to find out the entity_id and entity_type_id.
    $entity_id = NULL;
    $entity_type_id = NULL;
    $id_parts = explode('_', $behavior_setting->id());

    // Handle settings without entity bundle.
    if (count($id_parts) === 1) {
      $behavior_setting->set('entity_type_id', $behavior_setting->id());
      $behavior_setting->save();
      continue;
    }

    while (!$entity_type_manager->hasHandler($entity_type_id, 'storage') && !empty($id_parts)) {
      array_pop($id_parts);
      $entity_type_id = implode('_', $id_parts);
    }

    // Get a substring of the id. Do the string length +1 to skip the leading
    // underscore.
    $entity_id = substr($behavior_setting->id(), strlen($entity_type_id) + 1);

    // If the bundle entity does no longer exist, the rabbit_hole
    // behavior_setting is no longer necessary.
    if (!$entity_type_manager->getStorage($entity_type_id)->load($entity_id)) {
      $behavior_setting->delete();
    }
    else {
      $behavior_setting->set('entity_type_id', $entity_type_id);
      $behavior_setting->set('entity_id', $entity_id);
      $behavior_setting->save();
    }
  }
}
