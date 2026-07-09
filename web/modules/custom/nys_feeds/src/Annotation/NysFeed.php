<?php

namespace Drupal\nys_feeds\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for NYS Feeds object plugins.
 *
 * Plugin Namespace: Plugin\NysFeeds.
 *
 * @Annotation
 */
class NysFeed extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * A short description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * Recognized query string parameters.
   *
   * Should be implemented as an array in the form:
   *   [ 'param_name' => 'default value if not present', ... ]
   *
   * @var array
   */
  public array $params = [];

}
