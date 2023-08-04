<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.9 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('users_roles', array(
  'fields' => array(
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'rid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
  ),
  'primary key' => array(
    'uid',
    'rid',
  ),
  'indexes' => array(
    'rid' => array(
      'rid',
    ),
  ),
  'mysql_character_set' => 'utf8',
));