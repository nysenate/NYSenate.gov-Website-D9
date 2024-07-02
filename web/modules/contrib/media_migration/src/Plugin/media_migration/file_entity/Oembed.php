<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

use Drupal\Core\Database\Connection;

/**
 * Oembed media migration plugin for Oembed video media entities.
 *
 * @FileEntityDealer(
 *   id = "oembed_video",
 *   types= {},
 *   schemes = {"oembed"},
 *   destination_media_source_plugin_id = "oembed:video"
 * )
 */
class Oembed extends RemoteVideoBase {

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {
    $migration_definition['process'][$this->getDestinationMediaSourceFieldName() . '/value'] = [
      'plugin' => 'media_oembed_field_value',
      'source' => 'uri',
    ];
  }

}
