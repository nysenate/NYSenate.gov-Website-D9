<?php

namespace Drupal\views_autocomplete_filters\Plugin\views\filter;

/**
 * Define extra methods for Autocomplete filters.
 */
interface ViewsAutocompleteFiltersInterface {

  /**
   * Returns of the handler has 'autocomplete_field' selector.
   *
   * @return boolean
   */
  public function hasAutocompleteFieldSelector();

}
