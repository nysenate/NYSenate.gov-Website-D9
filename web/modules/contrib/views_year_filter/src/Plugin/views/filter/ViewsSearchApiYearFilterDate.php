<?php

namespace Drupal\views_year_filter\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiDate;
use Drupal\views_year_filter\DateViewsTrait;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_date_with_more_options")
 */
class ViewsSearchApiYearFilterDate extends SearchApiDate {

  use DateViewsTrait;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      $form['value']['type'] = [
        '#type'          => 'radios',
        '#title'         => $this->t('Value type'),
        '#options'       => [
          'date'      => $this->t('A date in any machine readable format. CCYY-MM-DD HH:MM:SS is preferred.'),
          'date_year' => $this->t('A date in yyyy format.'),
          'offset'    => $this->t('An offset from the current time such as "@example1" or "@example2"', [
            '@example1' => '+1 day',
            '@example2' => '-2 hours -30 minutes',
          ]),
        ],
        '#default_value' => $this->value['type'] ?? 'date',
      ];
      // Add js to handle year filter state.
      $form['#attached']['library'][] = 'views_year_filter/year_filter';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    // If year filter selected.
    if (!empty($this->value['type']) && $this->value['type'] == 'date_year' && isset($this->value['value'])) {
      // Get the value.
      $startDate = intval($this->value['value']) . '-01-01 00:00:01';
      $endDate = intval($this->value['value']) . '-12-31 23:59:59';

      $this->getQuery()->addCondition(
        $this->realField,
        [
          strtotime($startDate),
          strtotime($endDate),
        ],
        'BETWEEN',
        $this->options['group']
      );
    }
    else {
      parent::opSimple($field);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if (
      !empty($this->value['type']) &&
      $this->value['type'] == 'date_year' &&
      isset($this->value['min']) &&
      isset($this->value['max'])
    ) {
      $startDate = intval($this->value['min']) . '-01-01 00:00:01';
      $endDate = intval($this->value['max']) . '-12-31 23:59:59';
      $startDateConverted = strtotime($startDate, 0);
      $endDateConverted = strtotime($endDate, 0);
      $operator = strtoupper($this->operator);
      $group = $this->options['group'];
      $this->getQuery()->addCondition(
        $this->realField,
        [
          $startDateConverted,
          $endDateConverted,
        ],
        $operator,
        $group
      );
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
