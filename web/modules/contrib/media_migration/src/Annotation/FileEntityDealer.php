<?php

namespace Drupal\media_migration\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines FileEntityDealer annotation object.
 *
 * Plugin Namespace: Plugin\media_migration\file_entity.
 *
 * For a working example, see
 * \Drupal\media_migration\Plugin\media_migration\file_entity\Image.
 *
 * @see \Drupal\media_migration\FileEntityDealerManager
 * @see \Drupal\media_migration\FileEntityDealerPluginInterface
 * @see \Drupal\media_migration\FileEntityDealerBase
 * @see plugin_api
 *
 * @Annotation
 */
class FileEntityDealer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The file entity types the plugin applies to.
   *
   * @var string[]
   */
  public $types;

  /**
   * The schemes the plugin applies to.
   *
   * Optional.
   *
   * @var string[]
   */
  public $schemes = [];

  /**
   * The ID of the destination media type's source plugin.
   *
   * Optional.
   *
   * @var string
   */
  public $destination_media_source_plugin_id = '';

  /**
   * The ID of the destination media type's base ID.
   *
   * Optional.
   *
   * @var string
   */
  public $destination_media_type_id_base = '';

}
