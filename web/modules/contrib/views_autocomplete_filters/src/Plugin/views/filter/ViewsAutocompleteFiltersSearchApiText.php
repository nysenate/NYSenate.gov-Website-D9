<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\search_api\Plugin\views\filter\SearchApiText;

/**
 * Autocomplete for Search API fulltext fields to handle fulltext filtering.
 *
 * @ingroup views_filter_handlers
 */
class ViewsAutocompleteFiltersSearchApiText extends SearchApiText {

  // Exposed filter options.
  var $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

}
