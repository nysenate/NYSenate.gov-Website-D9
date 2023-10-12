<?php

/**
 * @file
 * Contains database additions to drupal-9.4.0.bare.standard.php.gz.
 *
 * This fixture enables the honeypot module by setting system configuration
 * values in the {config} and {key_value} tables, then adds the Honeypot
 * tables to the database using the schema from Honeypot version 8.x-1.22.
 *
 * This fixture is intended for use in testing honeypot_update_8100() and
 * honeypot_update_8101().
 *
 * @see https://www.drupal.org/node/3121331.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Create the Honeypot tables. This is from Honeypot version 8.x-1.22.
$connection->schema()->createTable('honeypot_user', [
  'description' => 'Table that stores failed attempts to submit a form.',
  'fields' => [
    'uid' => [
      'description' => 'Foreign key to {users}.uid; uniquely identifies a Drupal user to whom this ACL data applies.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'timestamp' => [
      'description' => 'Date/time when the form submission failed, as Unix timestamp.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
  ],
  'indexes' => [
    'uid' => ['uid'],
    'timestamp' => ['timestamp'],
  ],
]);

// Set the honeypot DB schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'honeypot',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension to enable honeypot.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['honeypot'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
