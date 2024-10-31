<?php

namespace Drupal\nys_bills\Plugin\views\argument;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\argument\NumericArgument;

/**
 * Contextual filter for displaying a bill, and it's amendment versions.
 *
 * @ViewsArgument("bill_amendments_contextual_filter")
 */
class BillAmendmentsContextualFilter extends NumericArgument {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $node = Node::load($this->argument);
    if ($node) {
      $tables_and_fields_to_match = [
        'node__field_ol_session' => 'field_ol_session',
        'node__field_ol_base_print_no' => 'field_ol_base_print_no',
      ];
      foreach ($tables_and_fields_to_match as $table => $field_name) {
        if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
          // Return nothing if Bill doesn't have required field values.
          $this->query->addWhere(0, 'node_field_data.nid', 0, '=');
          return;
        }
        $field_value = $node->get($field_name)->value;
        $db_field = $field_name . '_value';
        $this->query->addTable($table);
        $this->query->addWhere(0, "$table.$db_field", $field_value, '=');
      }
    }
  }

}
