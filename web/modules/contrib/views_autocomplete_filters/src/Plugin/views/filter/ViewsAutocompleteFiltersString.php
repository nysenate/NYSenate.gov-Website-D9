<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Autocomplete for basic textfield filter to handle string filtering commands.
 *
 * Including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 */
class ViewsAutocompleteFiltersString extends StringFilter {

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  public $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

}
