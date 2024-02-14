<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Start of year filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("created_year")
 */
class CreatedYearFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $timezone = new \DateTimeZone('America/New_York');
    $current_datetime = new \DateTime('now', $timezone);
    $current_year = $current_datetime->format('Y');
    $years = range($current_year, $current_year - 30);
    $options = ['current_year' => 'Current year'] + array_combine($years, $years);
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Selected year'),
      '#options' => $options,
      '#default_value' => !empty($this->options['value']) ? $this->options['value'] : 'current_year',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function operatorForm(&$form, FormStateInterface $form_state) {
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator!'),
      '#options' => [
        'BETWEEN' => 'During selected year',
        '>=' => 'After start of selected year',
        '<' => 'Before start of selected year',
      ],
      '#default_value' => !empty($this->options['operator']) ? $this->options['operator'] : 'BETWEEN',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    $timezone = new \DateTimeZone('America/New_York');
    $where_field = "$this->table.$this->realField";

    // Get input value depending on context (in_array = exposed filter input).
    if (is_array($this->value)) {
      $input_value = $this->value[0];
    }
    else {
      $input_value = $this->value;
    }

    // Get selected year datetime object.
    if ($input_value == 'current_year') {
      $current_year_start = new \DateTime("first day of january", $timezone);
      $selected_year = $current_year_start->format('Y');
    }
    else {
      $selected_year = $input_value;
    }
    $selected_year_start = new \DateTime("first day of january $selected_year", $timezone);

    // Setup where_value based on operator.
    if ($this->operator == 'BETWEEN') {
      $following_year = $selected_year + 1;
      $next_year_start = new \DateTime("first day of january $following_year", $timezone);
      $where_value = [
        $selected_year_start->getTimestamp(),
        $next_year_start->getTimestamp(),
      ];
    }
    else {
      $where_value = $selected_year_start->getTimestamp();
    }

    // Update query from input.
    $this->query->addWhere(0, $where_field, $where_value, $this->operator);
  }

}
