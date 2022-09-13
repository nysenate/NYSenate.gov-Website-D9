<?php

namespace Drupal\media_migration\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines media_wysiwyg annotation object.
 *
 * @Annotation
 */
class MediaWysiwyg extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Map of source entity type IDs and their destination entity type IDs.
   *
   * @var string[]
   */
  public $entity_type_map;

}
