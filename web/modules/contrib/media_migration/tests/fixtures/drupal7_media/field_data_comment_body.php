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

$connection->schema()->createTable('field_data_comment_body', array(
  'fields' => array(
    'entity_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'bundle' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'language' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'comment_body_value' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ),
    'comment_body_format' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
  ),
  'primary key' => array(
    'entity_type',
    'entity_id',
    'deleted',
    'delta',
    'language',
  ),
  'indexes' => array(
    'entity_type' => array(
      'entity_type',
    ),
    'bundle' => array(
      'bundle',
    ),
    'deleted' => array(
      'deleted',
    ),
    'entity_id' => array(
      'entity_id',
    ),
    'revision_id' => array(
      'revision_id',
    ),
    'language' => array(
      'language',
    ),
    'comment_body_format' => array(
      array(
        'comment_body_format',
        '191',
      ),
    ),
  ),
  'mysql_character_set' => 'utf8',
));
