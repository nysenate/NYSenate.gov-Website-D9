<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.10 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('node_access', array(
  'fields' => array(
    'nid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'gid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'realm' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'grant_view' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'grant_update' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'grant_delete' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
      'unsigned' => TRUE,
    ),
  ),
  'primary key' => array(
    'nid',
    'gid',
    'realm',
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('node_access')
->fields(array(
  'nid',
  'gid',
  'realm',
  'grant_view',
  'grant_update',
  'grant_delete',
))
->values(array(
  'nid' => '0',
  'gid' => '0',
  'realm' => 'all',
  'grant_view' => '1',
  'grant_update' => '0',
  'grant_delete' => '0',
))
->execute();