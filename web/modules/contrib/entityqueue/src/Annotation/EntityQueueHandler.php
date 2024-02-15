<?php

namespace Drupal\entityqueue\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EntityQueueHandler annotation object.
 *
 * Plugin Namespace: Plugin\EntityQueueHandler.
 *
 * @see \Drupal\entityqueue\EntityQueueHandlerInterface
 * @see \Drupal\entityqueue\EntityQueueHandlerManager
 * @see \Drupal\entityqueue\EntityQueueHandlerBase
 * @see plugin_api
 *
 * @Annotation
 */
class EntityQueueHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the queue handler plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The description of the queue handler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
