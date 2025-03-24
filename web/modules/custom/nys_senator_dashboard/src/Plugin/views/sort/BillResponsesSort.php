<?php

namespace Drupal\nys_senator_dashboard\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom sort plugin for the senator dashboard view.
 *
 * @ViewsSort("nys_senator_dashboard_bill_responses_sort")
 */
class BillResponsesSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $form['nys_senator_dashboard_bill_responses_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => [
        'publish_date_newest' => $this->t('Publish date - newest'),
        'publish_date_oldest' => $this->t('Publish date - oldest'),
        'print_number_desc' => $this->t('Print number - descending'),
        'print_number_asc' => $this->t('Print number - ascending'),
      ],
      '#default_value' => 'publish_date_newest',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $sort_field_mapping = [
      'publish_date_oldest' => ['field' => 'field_ol_publish_date', 'direction' => 'ASC'],
      'publish_date_newest' => ['field' => 'field_ol_publish_date', 'direction' => 'DESC'],
      'print_number_asc' => ['field' => 'field_ol_print_no', 'direction' => 'ASC'],
      'print_number_desc' => ['field' => 'field_ol_print_no', 'direction' => 'DESC'],
    ];

    $sort_option = $this->options['sort_option'];
    if (isset($sort_field_mapping[$sort_option])) {
      $field = $sort_field_mapping[$sort_option]['field'];
      $direction = $sort_field_mapping[$sort_option]['direction'];

      if (!empty($this->definition['relationship'])) {
        $this->query->addOrderBy($this->ensureMyTable(), $field, $direction, $this->relationship);
      } else {
        $this->query->addOrderBy($this->ensureMyTable(), $field, $direction);
      }
    }
  }

}
