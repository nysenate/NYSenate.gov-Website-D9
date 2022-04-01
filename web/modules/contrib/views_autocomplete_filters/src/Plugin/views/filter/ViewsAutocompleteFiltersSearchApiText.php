<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\search_api\Plugin\views\filter\SearchApiText;

/**
 * Autocomplete for Search API fulltext fields to handle fulltext filtering.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_autocomplete_filters_search_api_text")
 */
class ViewsAutocompleteFiltersSearchApiText extends SearchApiText implements ViewsAutocompleteFiltersInterface {

  // Exposed filter options.
  var $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

}
