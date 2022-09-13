<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

use Drupal\Core\Database\Connection;
use Drupal\media_migration\FileEntityDealerBase;
use Drupal\migrate\Row;

/**
 * Abstract plugin class for remote video media migration source plugins.
 */
abstract class RemoteVideoBase extends FileEntityDealerBase {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeIdBase() {
    return 'remote_video';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeId() {
    return $this->getDestinationMediaTypeIdBase();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeSourceFieldLabel() {
    return 'Video URL';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeLabel() {
    return 'Remote video';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    return 'field_media_oembed_video';
  }

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {
    $migration_definition['process'][$this->getDestinationMediaSourceFieldName() . '/value'] = [
      'plugin' => 'media_internet_field_value',
      'source' => 'uri',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldFormatterRow($row, $connection);
    $options = [
      'type' => 'oembed',
    ] + $row->getSourceProperty('options') ?? [];
    $row->setSourceProperty('options', $options);
  }

}
