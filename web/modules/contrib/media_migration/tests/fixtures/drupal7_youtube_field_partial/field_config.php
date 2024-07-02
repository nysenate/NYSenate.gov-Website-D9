<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.6 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('field_config')
->fields(array(
  'id',
  'field_name',
  'type',
  'module',
  'active',
  'storage_type',
  'storage_module',
  'storage_active',
  'locked',
  'data',
  'cardinality',
  'translatable',
  'deleted',
))
->values(array(
  'id' => '101',
  'field_name' => 'field_youtube_link',
  'type' => 'youtube',
  'module' => 'youtube',
  'active' => '1',
  'storage_type' => 'field_sql_storage',
  'storage_module' => 'field_sql_storage',
  'storage_active' => '1',
  'locked' => '0',
  'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:0:{}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:29:"field_data_field_youtube_link";a:2:{s:5:"input";s:24:"field_youtube_link_input";s:8:"video_id";s:27:"field_youtube_link_video_id";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:33:"field_revision_field_youtube_link";a:2:{s:5:"input";s:24:"field_youtube_link_input";s:8:"video_id";s:27:"field_youtube_link_video_id";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:8:"video_id";a:1:{i:0;s:8:"video_id";}}s:2:"id";s:3:"101";}',
  'cardinality' => '1',
  'translatable' => '0',
  'deleted' => '0',
))
->values(array(
  'id' => '102',
  'field_name' => 'field_youtube_field',
  'type' => 'youtube',
  'module' => 'youtube',
  'active' => '1',
  'storage_type' => 'field_sql_storage',
  'storage_module' => 'field_sql_storage',
  'storage_active' => '1',
  'locked' => '0',
  'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:0:{}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:30:"field_data_field_youtube_field";a:2:{s:5:"input";s:25:"field_youtube_field_input";s:8:"video_id";s:28:"field_youtube_field_video_id";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:34:"field_revision_field_youtube_field";a:2:{s:5:"input";s:25:"field_youtube_field_input";s:8:"video_id";s:28:"field_youtube_field_video_id";}}}}}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:8:"video_id";a:1:{i:0;s:8:"video_id";}}s:2:"id";s:3:"102";}',
  'cardinality' => '1',
  'translatable' => '0',
  'deleted' => '0',
))
->execute();
