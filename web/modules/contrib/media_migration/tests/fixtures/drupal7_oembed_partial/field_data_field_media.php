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

$connection->insert('field_data_field_media')
->fields(array(
  'entity_type' => 'node',
  'bundle' => 'oembed_content',
  'deleted' => '0',
  'entity_id' => '83863',
  'revision_id' => '83863',
  'language' => 'und',
  'delta' => '0',
  'field_media_fid' => '83863',
  'field_media_display' => '1',
  'field_media_description' => '',
))
->execute();