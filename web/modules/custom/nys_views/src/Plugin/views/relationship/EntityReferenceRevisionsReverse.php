<?php

namespace Drupal\nys_views\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Views;

/**
 * Relationship handler for reverse entity reference revisions relationships.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_reference_revisions_reverse")
 */
class EntityReferenceRevisionsReverse extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // First, relate our base table to the current base table to the
    // field, using the base table's id field.
    $table_data = Views::viewsData()->get($this->definition['base']);
    $base_field = empty($this->definition['base field']) ? $table_data['table']['base']['field'] : $this->definition['base field'];

    $join_type = empty($this->options['required']) ? 'LEFT' : 'INNER';

    // Join the entity_reference_revisions field table.
    $field_table = 'node__' . $this->definition['field name'];

    // Add the join to the query.
    $join = Views::pluginManager('join')->createInstance('standard', [
      'type' => $join_type,
      'table' => $field_table,
      'field' => $this->definition['field name'] . '_target_id',
      'left_table' => $this->tableAlias,
      'left_field' => $this->definition['field'],
      'operator' => '=',
      'extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ]);

    $alias = $this->query->addRelationship($this->definition['field name'] . '_' . $this->tableAlias, $join, $this->definition['base'], $this->relationship);

    // Now relate the field table to the base table.
    $second_join = Views::pluginManager('join')->createInstance('standard', [
      'type' => $join_type,
      'table' => $this->definition['base'],
      'field' => $base_field,
      'left_table' => $alias,
      'left_field' => 'entity_id',
      'operator' => '=',
    ]);

    $second_alias = $this->query->addRelationship($this->definition['field name'] . '_' . $this->definition['base'], $second_join, $this->definition['base'], $this->relationship);

    // Add the correct alias to the relationship.
    $this->alias = $second_alias;
  }

}
