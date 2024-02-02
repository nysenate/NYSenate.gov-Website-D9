<?php

namespace Drupal\entityqueue\Plugin\views\join;

use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\views\Plugin\views\join\JoinPluginBase;

/**
 * Implementation for the "casted_field_join" join.
 *
 * This is needed because subqueues are using a single table for tracking the
 * relationship to their items, but the referenced item IDs can be either
 * integers or strings and most DB engines (with MySQL as a notable exception)
 * are strict when comparing numbers and strings.
 *
 * @ingroup views_join_handlers
 *
 * @ViewsJoin("casted_field_join")
 */
class CastedFieldJoin extends JoinPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    if (empty($this->configuration['table formula'])) {
      $right_table = $this->table;
    }
    else {
      $right_table = $this->configuration['table formula'];
    }

    if ($this->leftTable) {
      $left_table = $view_query->getTableInfo($this->leftTable);
      $left_field = "$left_table[alias].$this->leftField";
    }
    else {
      // This can be used if left_field is a formula or something. It should be
      // used only *very* rarely.
      $left_field = $this->leftField;
      $left_table = NULL;
    }

    $right_field = "{$table['alias']}.$this->field";

    // Determine whether the left field of the relationship is an integer so we
    // know whether a CAST() is needed for the right field.
    if (isset($this->configuration['entity_type'])) {
      $field_storage_definition = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($this->configuration['entity_type'])[$this->leftField];
      if (is_a($field_storage_definition->getItemDefinition()->getClass(), IntegerItem::class, TRUE)) {
        switch (\Drupal::database()->databaseType()) {
          case 'mysql':
            $cast_data_type = 'UNSIGNED';
            break;

          default:
            $cast_data_type = 'INTEGER';
            break;
        }

        $right_field = "CAST($right_field AS $cast_data_type)";
      }
    }

    $condition = "$left_field = $right_field";
    $arguments = [];

    // Tack on the extra.
    if (isset($this->extra)) {
      $this->joinAddExtra($arguments, $condition, $table, $select_query, $left_table);
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }

}
