<?php

/**
 * @file
 * Database addition for entity_usage_update_8206() testing.
 *
 * @see https://www.drupal.org/project/entity_usage/issues/3335488
 * @see \Drupal\Tests\entity_usage\Functional\Update\UpdateTest::testUpdate8206()
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions, ['allowed_classes' => FALSE]);
$extensions['module']['entity_usage'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'entity_usage',
    'value' => 'i:8205;',
  ])
  ->execute();

// Create the  {entity_usage} table but use the old schema, which was in place
// before entity_usage_update_8206().
$connection->schema()->createTable('entity_usage', [
  'description' => 'Track entities that reference other entities.',
  'fields' => [
    'target_id' => [
      'description' => 'The target entity ID.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'target_id_string' => [
      'description' => 'The target ID, when the entity uses string IDs.',
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'target_type' => [
      'description' => 'The target entity type.',
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'source_id' => [
      'description' => 'The source entity ID.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'source_id_string' => [
      'description' => 'The source ID, when the entity uses string IDs.',
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => FALSE,
    ],
    'source_type' => [
      'description' => 'The source entity type.',
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'source_langcode' => [
      'description' => 'The source entity language code.',
      'type' => 'varchar_ascii',
      'length' => 12,
      'not null' => TRUE,
      'default' => '',
    ],
    'source_vid' => [
      'description' => 'The source entity revision ID.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
    'method' => [
      'description' => 'The method used to track the target, generally the plugin ID.',
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'field_name' => [
      'description' => 'The field in the source entity containing the target entity.',
      'type' => 'varchar_ascii',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
    ],
    'count' => [
      'description' => 'The number of times the target entity is referenced in this case.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ],
  ],
  'primary key' => [
    'target_id',
    'target_id_string',
    'target_type',
    'source_id',
    'source_type',
    'source_langcode',
    'source_vid',
    'method',
    'field_name',
  ],
  'indexes' => [
    'target_entity' => ['target_type', 'target_id'],
    'target_entity_string' => ['target_type', 'target_id_string'],
    'source_entity' => ['source_type', 'source_id'],
    'source_entity_string' => ['source_type', 'source_id_string'],
  ],
]);
