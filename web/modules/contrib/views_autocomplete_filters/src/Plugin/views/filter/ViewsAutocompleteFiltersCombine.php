<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Combine;

/**
 * Autocomplete for Combine fields filter which allows to search on multiple
 * fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_autocomplete_filters_combine")
 */
class ViewsAutocompleteFiltersCombine extends Combine implements ViewsAutocompleteFiltersInterface {

  // Exposed filter options.
  var $alwaysMultiple = TRUE;

  use ViewsAutocompleteFiltersTrait;

  /**
   * {@inheritdoc}
   */
  public function hasAutocompleteFieldSelector() {
    return FALSE;
  }

}
