<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\search_api\Plugin\views\filter\SearchApiFulltext;

/**
 * Autocomplete for Search API fulltext search for the view.
 *
 * It handles fulltext search filtering.
 *
 * @ingroup views_filter_handlers
 */
class ViewsAutocompleteFiltersSearchApiFulltext extends SearchApiFulltext {

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  public $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

}
