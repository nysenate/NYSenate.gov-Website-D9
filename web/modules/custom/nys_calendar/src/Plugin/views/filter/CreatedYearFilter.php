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
    $static_options = [
      'all' => '- Any -',
      'current_year' => 'Current year',
    ];
    $dynamic_options = array_combine($years, $years);
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Selected year'),
      '#options' => $static_options + $dynamic_options,
      '#default_value' => !empty($this->options['value']) ? $this->options['value'] : 'all',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function operatorForm(&$form, FormStateInterface $form_state) {
    $default_value = 'BETWEEN';
    if (
      !empty($this->options['operator'])
      && $this->options['operator'] != '='
    ) {
      $default_value = $this->options['operator'];
    }

    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Show content from'),
      '#options' => [
        'all' => '- Any -',
        'BETWEEN' => 'Selected year',
        '>=' => 'After start of selected year',
        '<' => 'Before start of selected year',
      ],
      '#default_value' => $default_value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    foreach ($form['expose'] as $expose_field_key => $expose_field) {
      if (!empty($form['expose'][$expose_field_key]['#type'])) {
        $form['expose'][$expose_field_key]['#access'] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    // Build operator form element.
    $operator = $this->options['expose']['operator_id'];
    $op_wrapper = $this->options['expose']['identifier'] . '_wrapper';
    $this->buildValueWrapper($form, $op_wrapper);
    $this->operatorForm($form, $form_state);
    $form[$op_wrapper][$operator] = $form['operator'];
    unset($form['operator']);

    // Build value form element.
    $value = $this->options['expose']['identifier'];
    $val_wrapper = $value . '_wrapper';
    $this->buildValueWrapper($form, $val_wrapper);
    $this->valueForm($form, $form_state);
    $form[$val_wrapper][$value] = $form['value'];
    unset($form['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Get input value depending on context.
    if (is_array($this->value)) {
      $input_value = $this->value[0];
    }
    else {
      $input_value = $this->value;
    }

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
