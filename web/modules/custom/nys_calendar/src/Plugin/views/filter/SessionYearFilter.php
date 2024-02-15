<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

/**
 * Session year filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("session_year")
 */
class SessionYearFilter extends YearFilterBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Get input value depending on context.
    $input_value = is_array($this->value) ? $this->value[0] : $this->value;

    // Bypass query additions if either field set to 'all'.
    if ($input_value == 'all' || $this->operator == 'all') {
      return;
    }

    // Prepare value for query.
    $timezone = new \DateTimeZone('America/New_York');
    if ($input_value == 'current_year') {
      $current_year_start = new \DateTime("first day of january", $timezone);
      $selected_year = $current_year_start->format('Y');
    }
    else {
      $selected_year = $input_value;
    }
    $this->value = $selected_year;

    parent::query();
  }

}
