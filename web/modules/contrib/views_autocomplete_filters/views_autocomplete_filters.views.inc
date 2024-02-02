<?php
/**
 * @file
 * Integration with Views.
 */

use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersCombine;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersSearchApiFulltext;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersSearchApiText;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersString;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function views_autocomplete_filters_views_plugins_filter_alter(&$plugins) {
  // Extend String handler with autocomplete capabilities.
  if (isset($plugins['string'])) {
    $plugins['string']['class'] = ViewsAutocompleteFiltersString::class;
  }
  // Extend Combine filter handler with autocomplete capabilities.
  if (isset($plugins['combine'])) {
    $plugins['combine']['class'] = ViewsAutocompleteFiltersCombine::class;
  }
  // Extend SearchApiText filter handler with autocomplete capabilities.
  if (isset($plugins['search_api_text'])) {
    $plugins['search_api_text']['class'] = ViewsAutocompleteFiltersSearchApiText::class;
  }
  // Extend SearchApiFulltext filter handler with autocomplete capabilities.
  if (isset($plugins['search_api_fulltext'])) {
    $plugins['search_api_fulltext']['class'] = ViewsAutocompleteFiltersSearchApiFulltext::class;
  }
}
