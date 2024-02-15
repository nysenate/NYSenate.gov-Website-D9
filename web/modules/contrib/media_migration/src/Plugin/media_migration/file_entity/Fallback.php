<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

/**
 * Fallback plugin for unknown (custom) file entity types.
 *
 * @FileEntityDealer(
 *   id = "fallback",
 *   types = {},
 *   destination_media_source_plugin_id = "file"
 * )
 */
class Fallback extends FileBase {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourcePluginId() {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeIdBase() {
    return $this->configuration['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    return implode('_', array_filter([
      'field',
      'media',
      $this->getDestinationMediaTypeIdBase(),
      $this->configuration['scheme'] === 'public' ? NULL : $this->configuration['scheme'],
    ]));
  }

}
