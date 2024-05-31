<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Audio media migration plugin for local audio media.
 *
 * @FileEntityDealer(
 *   id = "audio",
 *   types = {"audio"},
 *   destination_media_type_id_base = "audio",
 *   destination_media_source_plugin_id = "audio_file"
 * )
 */
class Audio extends FileBase {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeSourceFieldLabel() {
    return 'Audio file';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeLabel() {
    return implode(' ', array_filter([
      'Audio',
      $this->configuration['scheme'] === 'public' ? NULL : "({$this->configuration['scheme']})",
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldFormatterRow($row, $connection);
    $options = [
      'type' => 'file_audio',
      'settings' => [
        'controls' => TRUE,
        'autoplay' => FALSE,
        'loop' => FALSE,
        'multiple_file_display_type' => 'tags',
      ],
    ] + $row->getSourceProperty('options') ?? [];
    $row->setSourceProperty('options', $options);
  }

}
