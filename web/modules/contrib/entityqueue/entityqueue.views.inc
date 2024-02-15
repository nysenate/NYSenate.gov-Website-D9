<?php

/**
 * @file
 * Provide views data for the Entityqueue module.
 */

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\entityqueue\Entity\EntityQueue;

/**
 * Implements hook_views_data_alter().
 */
function entityqueue_views_data_alter(array &$data) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_subqueue = $entity_type_manager->getDefinition('entity_subqueue');

  // Find all entity types that need an 'entityqueue' relationship.
  $target_entity_type_ids = [];
  $queues = EntityQueue::loadMultiple();
  foreach ($queues as $queue) {
    $target_entity_type_ids[$queue->getTargetEntityTypeId()] = TRUE;
  }

  // Filter entity types to those that have a 'views_data' handler and use a SQL
  // storage.
  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
  $entity_types = [];
  foreach (array_keys($target_entity_type_ids) as $entity_type_id) {
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    if ($entity_type->hasHandlerClass('views_data') && $entity_type_manager->getStorage($entity_type->id()) instanceof SqlEntityStorageInterface) {
      $entity_types[$entity_type_id] = $entity_type;
    }
  }

  foreach ($entity_types as $entity_type_id => $entity_type) {
    $field_name = 'items';
    $field_storage = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('entity_subqueue')[$field_name];
    $target_base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $entity_type_manager->getStorage('entity_subqueue')->getTableMapping();
    $columns = $table_mapping->getColumnNames($field_name);
    $subqueue_items_table_name = $table_mapping->getDedicatedDataTableName($field_storage);

    $data[$target_base_table]['entityqueue_relationship']['relationship'] = [
      'id' => 'entity_queue',
      'title' => t('@target_label queue', ['@target_label' => $entity_type->getLabel()]),
      'label' => t('@target_label queue', ['@target_label' => $entity_type->getLabel()]),
      'group' => t('Entityqueue'),
      'help' => t('Create a relationship from @target_label to an entityqueue.', ['@target_label' => $entity_type->getLabel()]),
      'base' => $entity_subqueue->getDataTable() ?: $entity_subqueue->getBaseTable(),
      'entity_type' => 'entity_subqueue',
      'base field' => $entity_subqueue->getKey('id'),
      'field_name' => $field_storage->getName(),
      'field table' => $subqueue_items_table_name,
      'field field' => $columns['target_id'],
    ];

    $data[$target_base_table]['entityqueue_relationship_in_queue']['sort'] = [
      'id' => 'entity_queue_in_queue',
      'group' => t('Entityqueue'),
      'title' => t('In @target_label queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'label' => t('In @target_label queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'help' => t('Filter to ensure a(n) @target_label IS or IS NOT in the related queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'field' => 'delta',
      'field table' => $subqueue_items_table_name,
      'field_name' => $field_name,
    ];

    $data[$target_base_table]['entityqueue_relationship']['sort'] = [
      'id' => 'entity_queue_position',
      'group' => t('Entityqueue'),
      'title' => t('@target_label Queue Position', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'label' => t('@target_label Queue Position', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'help' => t('Position of item in the @target_label queue.', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'field' => 'delta',
      'field table' => $subqueue_items_table_name,
      'field_name' => $field_name,
    ];

    $data[$target_base_table]['entityqueue_relationship']['filter'] = [
      'id' => 'entity_queue_in_queue',
      'type' => 'yes-no',
      'group' => t('Entityqueue'),
      'title' => t('@target_label In Queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'label' => t('@target_label In Queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'help' => t('Filter for entities that are available or not in the @target_label entity queue.', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'field table' => $subqueue_items_table_name,
      'field field' => $columns['target_id'],
    ];

    $data[$target_base_table]['entityqueue_relationship_position']['field'] = [
      'id' => 'entity_queue_position',
      'group' => t('Entityqueue'),
      'title' => t('@target_label Position In Queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'label' => t('@target_label Position In Queue', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'help' => t('Position of item in the @target_label queue.', [
        '@target_label' => $entity_type->getLabel(),
      ]),
      'field' => 'delta',
      'field table' => $subqueue_items_table_name,
    ];
  }
}
