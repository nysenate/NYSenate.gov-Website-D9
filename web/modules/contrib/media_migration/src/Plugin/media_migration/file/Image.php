<?php

namespace Drupal\media_migration\Plugin\media_migration\file;

use Drupal\Core\Database\Connection;
use Drupal\media_migration\FileDealerBase;
use Drupal\migrate\Row;

/**
 * Plugin for images.
 *
 * @FileDealer(
 *   id = "image",
 *   mimes = {"image"},
 *   destination_media_source_plugin_id = "image",
 *   destination_media_type_id_base = "image"
 * )
 */
class Image extends FileDealerBase {

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {
    parent::alterMediaEntityMigrationDefinition($migration_definition, $connection);
    $source_field_name = $this->getDestinationMediaSourceFieldName();
    $alt_property_process_pipeline = [
      'plugin' => 'null_coalesce',
      'source' => [
        'alt',
        'description',
      ],
    ];
    $migration_definition['process'][$source_field_name . '/alt'] = $alt_property_process_pipeline;
    $migration_definition['process'][$source_field_name . '/title'] = 'title';
    $migration_definition['process'][$source_field_name . '/width'] = 'width';
    $migration_definition['process'][$source_field_name . '/height'] = 'height';
    $migration_definition['process']['thumbnail/target_id'] = 'fid';
    $migration_definition['process']['thumbnail/alt'] = $alt_property_process_pipeline;
    $migration_definition['process']['thumbnail/title'] = 'title';
    $migration_definition['process']['thumbnail/width'] = 'width';
    $migration_definition['process']['thumbnail/height'] = 'height';
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldFormatterRow($row, $connection);
    $options = $row->getSourceProperty('options') ?? [];
    $options['settings'] = [
      'image_style' => 'large',
    ];
    $row->setSourceProperty('options', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaEntityRow(Row $row, Connection $connection): void {
    parent::prepareMediaEntityRow($row, $connection);

    $file_id = $row->getSourceProperty('fid');

    foreach ($this->getImageData($connection, $file_id) as $data_key => $data_value) {
      $row->setSourceProperty($data_key, $data_value);
    }

    foreach ($this->getFileData($connection, $file_id) as $data_key => $data_value) {
      $row->setSourceProperty($data_key, $data_value);
    }
  }

}
