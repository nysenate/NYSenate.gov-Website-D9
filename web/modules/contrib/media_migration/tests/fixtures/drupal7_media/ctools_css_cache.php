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

$connection->schema()->createTable('ctools_css_cache', array(
  'fields' => array(
    'cid' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
    ),
    'filename' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'css' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'big',
    ),
    'filter' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'tiny',
    ),
  ),
  'primary key' => array(
    'cid',
  ),
  'mysql_character_set' => 'utf8',
));