<?php

namespace Drupal\address_map_link\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Map link item annotation object.
 *
 * @see \Drupal\address_map_link\Plugin\MapLinkManager
 * @see plugin_api
 *
 * @Annotation
 */
class MapLink extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the map link.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
