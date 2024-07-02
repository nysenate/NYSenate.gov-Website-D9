<?php

namespace Drupal\media_migration\Utility;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\media_migration\MediaMigration;

/**
 * Utility class for source database specific routines.
 *
 * @ingroup utility
 */
class SourceDatabase {

  /**
   * Returns the name of the text fields found in the given Drupal 7 database.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   * @param string|null $source_entity_type_id
   *   The source entity type ID to filter for. Optional.
   * @param string|null $source_bundle
   *   The source bundle to filter for. Optional.
   *
   * @return string[]
   *   The field names of the active text fields found in the source database.
   */
  public static function getTextFields(Connection $connection, ?string $source_entity_type_id = NULL, ?string $source_bundle = NULL) :array {
    if (!\Drupal::moduleHandler()->moduleExists('text')) {
      return [];
    }

    $query = $connection->select('field_config', 'fc')
      ->fields('fc', ['field_name'])
      ->condition('fc.module', 'text')
      ->condition('fc.type', ['text', 'text_long', 'text_with_summary'], 'IN')
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fci.deleted', 0)
      ->groupBy('fc.field_name');

    if ($source_entity_type_id) {
      $query->condition('fci.entity_type', $source_entity_type_id);

      if ($source_bundle) {
        $query->condition('fci.bundle', $source_bundle);
      }
    }

    $query->join('field_config_instance', 'fci', 'fc.id = fci.field_id');
    try {
      return $query->execute()->fetchCol();
    }
    catch (DatabaseExceptionWrapper $e) {
    }
    return [];
  }

  /**
   * Returns text formats which are formatting text with <a> pointing to a file.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to the Drupal 7 source database.
   * @param string[]|string|null $field_names
   *   The fields to check for the given tag.
   * @param string|null $source_entity_type_id
   *   The source entity type ID  to filter for.
   * @param string|null $source_bundle
   *   The source bundle to filter for.
   *
   * @return string[]
   *   Array of text format IDs.
   */
  public static function getFormatsHavingFileLink(Connection $connection, $field_names = NULL, ?string $source_entity_type_id = NULL, ?string $source_bundle = NULL) :array {
    if (!\Drupal::moduleHandler()->moduleExists('linkit')) {
      return [];
    }

    $field_names = $field_names ?? self::getTextFields($connection, $source_entity_type_id, $source_bundle);

    if (empty($field_names)) {
      return [];
    }

    // Create a (very big) union query.
    $query = NULL;

    foreach ((array) $field_names as $field_name) {
      $revision_table_exists = $connection->schema()->tableExists("field_revision_{$field_name}");
      $table = $revision_table_exists
        ? "field_revision_{$field_name}"
        : "field_data_{$field_name}";

      if (!$revision_table_exists && $connection->schema()->tableExists($table)) {
        continue;
      }

      $union_query = $connection->select($table, $field_name)
        ->condition("{$field_name}.{$field_name}_value", MediaMigration::SQL_PATTERN_LINKIT_FILE_LINK, 'LIKE')
        ->groupBy("{$field_name}.{$field_name}_format");
      $union_query->addField($field_name, "{$field_name}_format", 'format');

      if ($source_entity_type_id) {
        $union_query->condition("{$field_name}.entity_type", $source_entity_type_id);

        if ($source_bundle) {
          $union_query->condition("{$field_name}.bundle", $source_bundle);
        }
      }

      if ($query instanceof SelectInterface) {
        $query->union($union_query);
      }
      else {
        $query = $union_query;
      }
    }

    try {
      // The query might return even 'NULL' format.
      return array_filter($query->execute()->fetchCol());
    }
    catch (DatabaseExceptionWrapper $e) {
    }
    return [];
  }

  /**
   * Returns text formats which are formatting the tag in a Drupal 7 instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of a Drupal 7 instance.
   * @param string $tag
   *   The HTML tag to look for.
   * @param string[]|string|null $field_names
   *   The fields to check for the given tag.
   * @param string|null $source_entity_type_id
   *   The source entity type ID to filter for.
   * @param string|null $source_bundle
   *   The source bundle to filter for.
   *
   * @return string[]
   *   Array of text formats which are formatting the specified tag.
   */
  public static function getFormatsUsingTag(Connection $connection, string $tag, $field_names = NULL, ?string $source_entity_type_id = NULL, ?string $source_bundle = NULL) :array {

    $field_names = $field_names ?? self::getTextFields($connection, $source_entity_type_id, $source_bundle);

    if (empty($field_names)) {
      return [];
    }

    static $cached;

    // Cache to avoid repeating these queries unnecessarily (when any of the
    // optional parameters is unspecified).
    $cid = implode(':', array_filter([
      $tag,
      is_string($field_names) ? $field_names : implode(',', $field_names),
      $source_entity_type_id,
      $source_bundle,
    ]));
    if (isset($cached[$cid])) {
      return $cached[$cid];
    }

    // Create a (very big) union query.
    $query = NULL;

    foreach ((array) $field_names as $field_name) {
      $revision_table_exists = $connection->schema()->tableExists("field_revision_{$field_name}");
      $table = $revision_table_exists
        ? "field_revision_{$field_name}"
        : "field_data_{$field_name}";

      if (!$revision_table_exists && $connection->schema()->tableExists($table)) {
        continue;
      }

      $union_query = $connection->select($table, $field_name)
        ->condition("{$field_name}.{$field_name}_value", '%<' . $tag . ' %', 'LIKE')
        ->groupBy("{$field_name}.{$field_name}_format");
      $union_query->addField($field_name, "{$field_name}_format", 'format');

      if ($source_entity_type_id) {
        $union_query->condition("{$field_name}.entity_type", $source_entity_type_id);

        if ($source_bundle) {
          $union_query->condition("{$field_name}.bundle", $source_bundle);
        }
      }

      if ($query instanceof SelectInterface) {
        $query->union($union_query);
      }
      else {
        $query = $union_query;
      }
    }

    try {
      // The query might return even 'NULL' format.
      $result = array_filter($query->execute()->fetchCol());
      $cached[$cid] = $result;
    }
    catch (DatabaseExceptionWrapper $e) {
      $cached[$cid] = [];
    }
    return $cached[$cid];
  }

}
