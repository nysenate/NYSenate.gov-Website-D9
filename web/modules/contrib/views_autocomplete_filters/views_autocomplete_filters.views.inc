<?php
/**
 * @file
 * Integration with Views.
 */

/**
 * Implements hook_views_data_alter().
 */
function views_autocomplete_filters_views_data_alter(array &$data) {
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      // Extend String handler with autocomplete capabilities.
      if (isset($field_data['filter']['id']) && $field_data['filter']['id'] == 'string') {
        $data[$table_name][$field_name]['filter']['id'] = 'views_autocomplete_filters_string';
      }
      // Extend Combine filter handler with autocomplete capabilities.
      if (isset($field_data['filter']['id']) && $field_data['filter']['id'] == 'combine') {
        $data[$table_name][$field_name]['filter']['id'] = 'views_autocomplete_filters_combine';
      }
      // Extend SearchApiText filter handler with autocomplete capabilities.
      if (isset($field_data['filter']['id']) && $field_data['filter']['id'] == 'search_api_text') {
        $data[$table_name][$field_name]['filter']['id'] = 'views_autocomplete_filters_search_api_text';
      }
      // Extend SearchApiFulltext filter handler with autocomplete capabilities.
      if (isset($field_data['filter']['id']) && $field_data['filter']['id'] == 'search_api_fulltext') {
        $data[$table_name][$field_name]['filter']['id'] = 'views_autocomplete_filters_search_api_fulltext';
      }
    }
  }
}
