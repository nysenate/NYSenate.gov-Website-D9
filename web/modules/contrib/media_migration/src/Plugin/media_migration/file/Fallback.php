<?php

namespace Drupal\media_migration\Plugin\media_migration\file;

use Drupal\Core\Database\Connection;
use Drupal\media_migration\FileDealerBase;
use Drupal\migrate\Row;

/**
 * General plugin for any kind of file.
 *
 * @FileDealer(
 *   id = "fallback"
 * )
 */
class Fallback extends FileDealerBase {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourcePluginId() {
    switch ($this->configuration['mime']) {
      case 'audio':
        return 'audio_file';

      case 'image':
        return 'image';

      case 'video':
        return 'video_file';

      default:
        return 'file';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeIdBase() {
    switch ($this->configuration['mime']) {
      case 'audio':
      case 'image':
      case 'video':
        return $this->configuration['mime'];

      default:
        return 'document';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    // Document's field name should be field_media_document[_private].
    if ($this->getDestinationMediaTypeIdBase() === 'document') {
      return implode('_', array_filter([
        'field',
        'media',
        'document',
        $this->configuration['scheme'] === 'public' ? NULL : $this->configuration['scheme'],
      ]));
    }
    return parent::getDestinationMediaSourceFieldName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeSourceFieldLabel() {
    switch ($this->getDestinationMediaSourcePluginId()) {
      case 'audio_file':
        return 'Audio file';

      case 'video_file':
        return 'Video file';
    }

    return parent::getDestinationMediaTypeSourceFieldLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {
    parent::alterMediaEntityMigrationDefinition($migration_definition, $connection);
    $source_field_name = $this->getDestinationMediaSourceFieldName();
    $migration_definition['process'][$source_field_name . '/display'] = 'display';
    $migration_definition['process'][$source_field_name . '/description'] = 'description';
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldInstanceRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldInstanceRow($row, $connection);
    $show_description_field = FALSE;
    foreach ($this->getFileFieldData($connection, FALSE) as $data) {
      if (!empty($data['field_instance_data']['settings']['description_field'])) {
        $show_description_field = TRUE;
        break 1;
      }
    }
    $row->setSourceProperty('settings/description_field', $show_description_field);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldFormatterRow($row, $connection);
    $original_options = $row->getSourceProperty('options') ?? [];

    switch ($this->getDestinationMediaSourcePluginId()) {
      case 'audio_file':
        $options = [
          'type' => 'file_audio',
          'settings' => [
            'controls' => TRUE,
            'autoplay' => FALSE,
            'loop' => FALSE,
            'multiple_file_display_type' => 'tags',
          ],
        ] + $original_options;
        $row->setSourceProperty('options', $options);
        break;

      case 'video_file':
        $options = [
          'type' => 'file_video',
          'settings' => [
            'muted' => FALSE,
            'width' => 640,
            'height' => 480,
          ],
        ] + $original_options;
        $row->setSourceProperty('options', $options);
        break;

      case 'image':
        $original_options['settings'] = [
          'image_style' => 'large',
        ];
        $row->setSourceProperty('options', $original_options);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaEntityRow(Row $row, Connection $connection): void {
    parent::prepareMediaEntityRow($row, $connection);

    foreach ($this->getFileData($connection, $row->getSourceProperty('fid')) as $data_key => $data_value) {
      $row->setSourceProperty($data_key, $data_value);
    }
  }

}
