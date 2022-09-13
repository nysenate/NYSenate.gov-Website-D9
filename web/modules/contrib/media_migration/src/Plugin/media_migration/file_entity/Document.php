<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

/**
 * Document media migration plugin for local document media entities.
 *
 * @FileEntityDealer(
 *   id = "document",
 *   types = {"document"},
 *   destination_media_type_id_base = "document",
 *   destination_media_source_plugin_id = "file"
 * )
 */
class Document extends FileBase {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    return 'field_media_document';
  }

}
