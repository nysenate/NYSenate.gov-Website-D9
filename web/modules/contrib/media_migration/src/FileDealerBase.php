<?php

namespace Drupal\media_migration;

use Drupal\Core\Database\Connection;

/**
 * Base implementation of file dealer plugins.
 */
abstract class FileDealerBase extends MediaDealerBase implements FileDealerPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {
    $migration_definition['process'][$this->getDestinationMediaSourceFieldName() . '/target_id'] = 'fid';
  }

  /**
   * Get the name of the file fields from the source database.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   * @param bool $field_names_only
   *   Whether only the name of the file fields should be returned. Defaults to
   *   TRUE.
   *
   * @return array
   *   The array of the available file fields.
   */
  protected function getFileFieldData(Connection $connection, bool $field_names_only = TRUE): array {
    $field_query = $connection->select('field_config', 'fs')
      ->fields('fs', ['field_name'])
      ->condition('fs.type', 'file')
      ->condition('fs.active', 1)
      ->condition('fs.deleted', 0)
      ->condition('fs.storage_active', 1)
      ->condition('fi.deleted', 0);
    $field_query->join('field_config_instance', 'fi', 'fs.id = fi.field_id');

    if ($field_names_only) {
      return array_keys($field_query->execute()->fetchAllAssoc('field_name'));
    }

    $field_query->addField('fs', 'data', 'field_storage_data');
    $field_query->addField('fi', 'data', 'field_instance_data');

    $fields_data = [];
    foreach ($field_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $item) {
      foreach (['field_storage_data', 'field_instance_data'] as $data_key) {
        $item[$data_key] = unserialize($item[$data_key]);
      }
      $fields_data[] = $item;
    }

    return $fields_data;
  }

  /**
   * Returns display and description properties of the specified file.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   * @param string|int $file_id
   *   The ID of the file.
   *
   * @return array
   *   An array of those properties whose value is not empty.
   */
  protected function getFileData(Connection $connection, $file_id): array {
    foreach ($this->getFileFieldData($connection) as $field_name) {
      $field_table_name = "field_data_$field_name";
      $data_query = $connection->select($field_table_name, $field_name);
      $data_query->addField($field_name, "{$field_name}_display", 'display');
      $data_query->addField($field_name, "{$field_name}_description", 'description');
      $data_query->condition("{$field_name}_fid", $file_id);

      if (!empty($results = $data_query->execute()->fetchAll(\PDO::FETCH_ASSOC))) {
        $result = reset($results);
        return array_filter($result);
      }
    }

    return [];
  }

}
