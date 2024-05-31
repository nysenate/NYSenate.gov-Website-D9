<?php

namespace Drupal\node_revision_delete\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a node revision delete plugin annotation object.
 *
 * @see \Drupal\node_revision_delete\Plugin\NodeRevisionDeletePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class NodeRevisionDelete extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

}
