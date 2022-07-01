<?php

namespace Drupal\nys_openleg_imports\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for Openleg import processor plugins.
 *
 * Plugin Namespace: Plugin\OpenlegImportProcessor.
 *
 * @Annotation
 */
class OpenlegImportProcessor extends Plugin {

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
   * The machine name of the node bundle receiving the import.
   *
   * @var string
   */
  public string $bundle;

}
