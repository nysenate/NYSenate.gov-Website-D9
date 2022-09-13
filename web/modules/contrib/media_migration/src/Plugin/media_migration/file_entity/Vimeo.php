<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

/**
 * Vimeo media migration plugin for Vimeo media entities.
 *
 * @FileEntityDealer(
 *   id = "vimeo",
 *   types = {"video"},
 *   schemes = {"vimeo"},
 *   destination_media_source_plugin_id = "oembed:video"
 * )
 */
class Vimeo extends RemoteVideoBase {

}
