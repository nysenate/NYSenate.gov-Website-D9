<?php

namespace Drupal\media_migration\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines FileDealer annotation object.
 *
 * Plugin Namespace: Plugin\media_migration\file.
 *
 * For a working example, see
 * \Drupal\media_migration\Plugin\media_migration\file\Image.
 *
 * @see \Drupal\media_migration\FileDealerManager
 * @see \Drupal\media_migration\FileDealerPluginInterface
 * @see \Drupal\media_migration\FileDealerBase
 * @see plugin_api
 *
 * @Annotation
 */
class FileDealer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The schemes the plugin applies to.
   *
   * Optional.
   *
   * @var string[]
   */
  public $schemes = [];

  /**
   * The main MIME typed the plugin applies to.
   *
   * Optional.
   *
   * @var string[]
   */
  public $mimes = [];

  /**
   * The ID of the destination media type's source plugin.
   *
   * Optional.
   *
   * @var string
   */
  public $destination_media_source_plugin_id = '';

  /**
   * The destination media type's base ID.
   *
   * Optional.
   *
   * @var string
   */
  public $destination_media_type_id_base = '';

}
