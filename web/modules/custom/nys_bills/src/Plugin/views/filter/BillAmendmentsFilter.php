<?php

namespace Drupal\nys_bills\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter to choose between a bill's amendments.
 *
 * @ViewsFilter("bill_amendments_filter")
 */
class BillAmendmentsFilter extends FilterPluginBase {

  /**
   * {@inheritDoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $node_storage = \Drupal::entityTypeManager()
      ->getStorage('node');
    $current_node = \Drupal::routeMatch()
      ->getParameter('node');
    if (empty($current_node) && !empty($form_state->getUserInput()['bill_amendment_filter'])) {
      $current_node = $node_storage->load($form_state->getUserInput()['bill_amendment_filter']);
    }
    if (
      !($current_node instanceof NodeInterface)
      || empty($current_node->field_ol_session->value)
      || empty($current_node->field_ol_base_print_no->value)
    ) {
      return;
    }

    $bills_nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ol_session', $current_node->field_ol_session->value)
      ->condition('field_ol_base_print_no', $current_node->field_ol_base_print_no->value)
      ->execute();
    $bills = $node_storage->loadMultiple($bills_nids);

    $options = [];
    foreach ($bills as $bill) {
      $options[$bill->id()] = $bill->getTitle();
    }

    $form['bill_amendment_filter'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $current_node->id(),
    ];
  }

  /**
   * Sets the value for the filter from the exposed form.
   */
  public function acceptExposedInput($input) {
    if (isset($input['bill_amendment_filter'])) {
      $this->value = $input['bill_amendment_filter'];
      return TRUE;
    }
    return FALSE;
  }

}
