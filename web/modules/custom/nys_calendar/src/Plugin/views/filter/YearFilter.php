<?php

namespace Drupal\nys_calendar\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Year filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("year_filter")
 */
class YearFilter extends FilterPluginBase {

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
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Show content from'),
      '#options' => [
        'all' => '- Any -',
        '=' => 'Selected year',
        '>=' => 'After start of selected year',
        '<' => 'Before start of selected year',
      ],
      '#default_value' => !empty($this->options['operator']) ? $this->options['operator'] : 'all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['use_operator']['#default_value'] = TRUE;
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
    // Make input value consistent regardless of filter context.
    $this->value = is_array($this->value) && !empty($this->value[0]) ? $this->value[0] : $this->value;

    // Bypass query additions if either field set to 'all'.
    if ($this->value == 'all' || $this->operator == 'all') {
      return;
    }

    // Dynamically set 'current_year' to current year.
    if ($this->value == 'current_year') {
      $timezone = new \DateTimeZone('America/New_York');
      $current_year = new \DateTime("now", $timezone);
      $this->value = $current_year->format('Y');
    }

    // Process value(s) and operator for datetime fields.
    if ($this->configuration['field_type'] == 'datetime') {
      $selected_year_start = new \DateTime("first day of january $this->value", $timezone);

      if ($this->operator == '=') {
        $this->operator = 'BETWEEN';
        $following_year = $this->value + 1;
        $next_year_start = new \DateTime("first day of january $following_year", $timezone);
        $this->value = [
          $selected_year_start->getTimestamp(),
          $next_year_start->getTimestamp(),
        ];
      }
      else {
        $this->value = $selected_year_start->getTimestamp();
      }
    }

    parent::query();
  }

}
