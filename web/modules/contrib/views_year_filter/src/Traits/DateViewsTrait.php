<?php

namespace Drupal\views_year_filter\Traits;

/**
 * Shared code between the YearFilterDate and YearFilterDatetime plugins.
 */
trait DateViewsTrait {

  /**
   * Apply the HTML5 date popup to the views filter form.
   *
   * @param array $form
   *   The form to apply it to.
   */
  protected function applyDatePopupToForm(array &$form) {
    $module_handler = \Drupal::service('module_handler');
    $identifier = $this->options['expose']['identifier'];

    // Identify wrapper.
    $wrapper_key = $identifier . '_wrapper';
    if (isset($form[$wrapper_key])) {
      $element = &$form[$wrapper_key][$identifier];
    }
    else {
      $element = &$form[$identifier];
    }

    // If the date pop module is enabled change the element type to date
    // instead of textfield.
    if (
      $module_handler->moduleExists('date_popup') &&
      isset($this->options['value']['type']) &&
      $this->options['value']['type'] !== 'date_year'
    ) {
      // Detect filters that are using min/max.
      if (isset($element['min'])) {
        $element['min']['#type'] = 'date';
        $element['max']['#type'] = 'date';
        if (isset($element['value'])) {
          $element['value']['#type'] = 'date';
        }
      }
      else {
        $element['#type'] = 'date';
      }
    }

    // Add Bootstrap Datepicker attributes.
    if (isset($this->options['value']['type']) && $this->options['value']['type'] === 'date_year') {
      // Get configuration.
      $config = \Drupal::config('views_year_filter.settings');
      if ($config->get('use_bootstrap_datepicker') == 1) {
        // Disable autocomplete widget.
        $element['#attributes']['autocomplete'] = 'off';
        // Add class to initiate datepicker.
        $element['#attributes']['class'][] = 'js-datepicker-years-filter';
        $element['#attached']['library'][] = 'views_year_filter/datepicker';
      }
    }
  }

}
