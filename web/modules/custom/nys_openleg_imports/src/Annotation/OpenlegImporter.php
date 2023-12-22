<?php

namespace Drupal\nys_openleg_imports\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for Openleg import plugins.
 *
 * Plugin Namespace: Plugin\OpenlegImporters.
 *
 * @Annotation
 */
class OpenlegImporter extends Plugin {

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
   * The name of the Openleg API Request plugin to use.
   *
   * @var string
   */
  public string $requester;

}
