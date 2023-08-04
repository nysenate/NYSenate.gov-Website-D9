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

$connection->schema()->createTable('filter', array(
  'fields' => array(
    'format' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
    ),
    'module' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => '',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'settings' => array(
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'big',
    ),
  ),
  'primary key' => array(
    'format',
    'name',
  ),
  'indexes' => array(
    'list' => array(
      'weight',
      'module',
      'name',
    ),
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('filter')
->fields(array(
  'format',
  'module',
  'name',
  'weight',
  'status',
  'settings',
))
->values(array(
  'format' => 'filtered_html',
  'module' => 'filter',
  'name' => 'filter_autop',
  'weight' => '2',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'filtered_html',
  'module' => 'filter',
  'name' => 'filter_html',
  'weight' => '1',
  'status' => '1',
  'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
))
->values(array(
  'format' => 'filtered_html',
  'module' => 'filter',
  'name' => 'filter_htmlcorrector',
  'weight' => '10',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'filtered_html',
  'module' => 'filter',
  'name' => 'filter_html_escape',
  'weight' => '-10',
  'status' => '0',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'filtered_html',
  'module' => 'filter',
  'name' => 'filter_url',
  'weight' => '0',
  'status' => '1',
  'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
))
->values(array(
  'format' => 'full_html',
  'module' => 'filter',
  'name' => 'filter_autop',
  'weight' => '1',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'full_html',
  'module' => 'filter',
  'name' => 'filter_html',
  'weight' => '-10',
  'status' => '0',
  'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
))
->values(array(
  'format' => 'full_html',
  'module' => 'filter',
  'name' => 'filter_htmlcorrector',
  'weight' => '10',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'full_html',
  'module' => 'filter',
  'name' => 'filter_html_escape',
  'weight' => '-10',
  'status' => '0',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'full_html',
  'module' => 'filter',
  'name' => 'filter_url',
  'weight' => '0',
  'status' => '1',
  'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
))
->values(array(
  'format' => 'plain_text',
  'module' => 'filter',
  'name' => 'filter_autop',
  'weight' => '2',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'plain_text',
  'module' => 'filter',
  'name' => 'filter_html',
  'weight' => '-10',
  'status' => '0',
  'settings' => 'a:3:{s:12:"allowed_html";s:74:"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>";s:16:"filter_html_help";i:1;s:20:"filter_html_nofollow";i:0;}',
))
->values(array(
  'format' => 'plain_text',
  'module' => 'filter',
  'name' => 'filter_htmlcorrector',
  'weight' => '10',
  'status' => '0',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'plain_text',
  'module' => 'filter',
  'name' => 'filter_html_escape',
  'weight' => '0',
  'status' => '1',
  'settings' => 'a:0:{}',
))
->values(array(
  'format' => 'plain_text',
  'module' => 'filter',
  'name' => 'filter_url',
  'weight' => '1',
  'status' => '1',
  'settings' => 'a:1:{s:17:"filter_url_length";i:72;}',
))
->execute();