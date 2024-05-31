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

$connection->schema()->createTable('field_revision_field_image', array(
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
      'not null' => TRUE,
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
    'field_image_fid' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_image_alt' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '512',
    ),
    'field_image_title' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '1024',
    ),
    'field_image_width' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_image_height' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
  ),
  'primary key' => array(
    'entity_type',
    'entity_id',
    'revision_id',
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
    'field_image_fid' => array(
      'field_image_fid',
    ),
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('field_revision_field_image')
->fields(array(
  'entity_type',
  'bundle',
  'deleted',
  'entity_id',
  'revision_id',
  'language',
  'delta',
  'field_image_fid',
  'field_image_alt',
  'field_image_title',
  'field_image_width',
  'field_image_height',
))
->values(array(
  'entity_type' => 'node',
  'bundle' => 'article',
  'deleted' => '0',
  'entity_id' => '1',
  'revision_id' => '1',
  'language' => 'und',
  'delta' => '0',
  'field_image_fid' => '1',
  'field_image_alt' => 'Alt for blue.png',
  'field_image_title' => '',
  'field_image_width' => '1280',
  'field_image_height' => '720',
))
->execute();
