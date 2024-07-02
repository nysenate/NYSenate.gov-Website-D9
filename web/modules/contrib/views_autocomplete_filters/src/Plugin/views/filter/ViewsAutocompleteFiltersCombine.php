<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Combine;

/**
 * Autocomplete for Combine fields filter.
 *
 * It allows searching on multiple fields.
 *
 * @ingroup views_filter_handlers
 */
class ViewsAutocompleteFiltersCombine extends Combine {

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  public $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

}
