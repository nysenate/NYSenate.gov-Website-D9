<?php

namespace Drupal\rabbit_hole\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Rabbit hole entity plugin item annotation object.
 *
 * @see \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
 * @see plugin_api
 *
 * @Annotation
 *
 * @deprecated in rabbit_hole:2.0.0 and is removed from rabbit_hole:3.0.0. Content entity types are supported by default now.
 *
 * @see https://www.drupal.org/node/3359194
 */
class RabbitHoleEntityPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The string id of the affected entity.
   *
   * @var string
   */
  public $entityType;

}
