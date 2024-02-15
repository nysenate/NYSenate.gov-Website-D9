<?php

namespace Drupal\media_migration\Plugin\media_migration\file_entity;

/**
 * Youtube media migration plugin for YouTube media entities.
 *
 * @FileEntityDealer(
 *   id = "youtube",
 *   types = {"video"},
 *   schemes = {"youtube"},
 *   destination_media_source_plugin_id = "oembed:video"
 * )
 */
class Youtube extends RemoteVideoBase {

}
