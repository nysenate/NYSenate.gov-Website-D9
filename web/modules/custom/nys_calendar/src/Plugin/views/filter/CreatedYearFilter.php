<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

/**
 * Start of year filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("created_year")
 */
class CreatedYearFilter extends YearFilterBase {

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

    // Get selected year datetime object.
    $timezone = new \DateTimeZone('America/New_York');
    if ($input_value == 'current_year') {
      $current_year_start = new \DateTime("first day of january", $timezone);
      $selected_year = $current_year_start->format('Y');
    }
    else {
      $selected_year = $input_value;
    }
    $selected_year_start = new \DateTime("first day of january $selected_year", $timezone);

    // Prepare value for query.
    if ($this->operator == 'BETWEEN') {
      $following_year = $selected_year + 1;
      $next_year_start = new \DateTime("first day of january $following_year", $timezone);
      $processed_value = [
        $selected_year_start->getTimestamp(),
        $next_year_start->getTimestamp(),
      ];
    }
    else {
      $processed_value = $selected_year_start->getTimestamp();
    }
    $this->value = $processed_value;

    parent::query();
  }

}
