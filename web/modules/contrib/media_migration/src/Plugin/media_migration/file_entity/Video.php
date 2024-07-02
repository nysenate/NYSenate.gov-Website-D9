<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Video media migration plugin for local video media entities.
 *
 * @FileEntityDealer(
 *   id = "video",
 *   types = {"video"},
 *   destination_media_type_id_base = "video",
 *   destination_media_source_plugin_id = "video_file"
 * )
 */
class Video extends FileBase {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeSourceFieldLabel() {
    return 'Video file';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeLabel() {
    return implode(' ', array_filter([
      'Video',
      $this->configuration['scheme'] === 'public' ? NULL : "({$this->configuration['scheme']})",
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    parent::prepareMediaSourceFieldFormatterRow($row, $connection);
    $options = [
      'type' => 'file_video',
      'settings' => [
        'muted' => FALSE,
        'width' => 640,
        'height' => 480,
      ],
    ] + $row->getSourceProperty('options') ?? [];
    $row->setSourceProperty('options', $options);
  }

}
