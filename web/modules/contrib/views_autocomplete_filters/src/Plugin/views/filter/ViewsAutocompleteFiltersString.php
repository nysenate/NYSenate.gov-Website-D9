<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Autocomplete for basic textfield filter to handle string filtering commands
 * including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_autocomplete_filters_string")
 */
class ViewsAutocompleteFiltersString extends StringFilter implements ViewsAutocompleteFiltersInterface {

  // Exposed filter options.
  var $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

}
