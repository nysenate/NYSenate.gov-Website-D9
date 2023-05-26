<?php

namespace Drupal\views_year_filter\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;
use Drupal\views_year_filter\DateViewsTrait;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date_with_more_options")
 */
class ViewsYearFilterDate extends Date {

  use DateViewsTrait;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      $form['value']['type']['#options']['date_year'] = $this->t('A date in CCYY format.');
      // Add js to handle year filter state.
      $form['#attached']['library'][] = 'views_year_filter/year_filter';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    // If year filter selected.
    if (
      !empty($this->value['type']) &&
      $this->value['type'] == 'date_year' &&
      isset($this->value['value']) &&
       filter_var($this->value['value'], FILTER_VALIDATE_INT)
    ) {
      // Get the value.
      $value = $this->value['value'] ?? '';
      // In Case of changed, created and published on date is timestamp.
      if (
        strpos($field, '.changed') !== FALSE ||
        strpos($field, '.created') !== FALSE ||
        strpos($field, '.published_at') !== FALSE
      ) {
        $this->query->addWhereExpression($this->options['group'], "YEAR(FROM_UNIXTIME($field)) $this->operator $value");
      }
      else {
        // Add Expression for dates / not timestamp.
        $this->query->addWhereExpression($this->options['group'], "YEAR($field) $this->operator $value");
      }
    }
    else {
      parent::opSimple($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    // If year filter selected.
    if (
      !empty($this->value['type']) &&
      $this->value['type'] == 'date_year' &&
      isset($this->value['min']) &&
      isset($this->value['max'])
    ) {
      $min = ($this->value['min']) ?? 0;
      $max = ($this->value['max']) ?? 0;
      $operator = strtoupper($this->operator);
      // In Case of changed, created and published on date is timestamp.
      if (
        strpos($field, '.changed') !== FALSE ||
        strpos($field, '.created') !== FALSE ||
        strpos($field, '.published_at') !== FALSE
      ) {
        $this->query->addWhereExpression($this->options['group'], "YEAR(FROM_UNIXTIME($field)) $operator $min AND $max");
      }
      else {
        $this->query->addWhereExpression($this->options['group'], "YEAR($field) $operator $min AND $max");
      }
    }
    else {
      parent::opBetween($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
