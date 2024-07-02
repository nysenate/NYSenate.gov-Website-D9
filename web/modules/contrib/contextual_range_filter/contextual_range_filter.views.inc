<?php

/**
 * @file
 * contextual_range_filter.views.inc
 *
 * Implementation of hook_views_data_alter().
 */

/**
 * Implements hook_views_data_alter().
 *
 * Replaces contextual filter plugins by their corresponding RANGE plugins.
 *
 * This function finds all field and node property argument handlers. If
 * they're listed on the Contextual Range Filter configuration page as "to be
 * converted", it swaps in the corresponding date, string or numeric range
 * plugin.
 */
function contextual_range_filter_views_data_alter(&$data) {

  $config = \Drupal::config('contextual_range_filter.settings');

  $date_field_names = array_filter($config->get('date_field_names') ?: []);
  $numeric_field_names = array_filter($config->get('numeric_field_names') ?: []);
  $string_field_names = array_filter($config->get('string_field_names') ?: []);

  if (!empty($date_field_names) || !empty($numeric_field_names) || !empty($string_field_names)) {

    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['argument']['id'])) {
          // If listed on the configuration page, replace this contextual filter
          // plugin by its corresponding contexutal RANGE filter plugin.
          $full_name = "$table_name:$field_name";
          if (in_array($full_name, $date_field_names)) {
            $data[$table_name][$field_name]['argument']['id'] = 'date_range';
          }
          elseif (in_array($full_name, $numeric_field_names)) {
            $data[$table_name][$field_name]['argument']['id'] = 'numeric_range';
          }
          elseif (in_array($full_name, $string_field_names)) {
            $data[$table_name][$field_name]['argument']['id'] = 'string_range';
          }
        }
      }
    }
  }
}
