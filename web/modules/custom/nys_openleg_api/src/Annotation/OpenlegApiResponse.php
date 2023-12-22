<?php

namespace Drupal\nys_openleg_api\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for Openleg response plugins.
 *
 * Plugin Namespace: Plugin\OpenlegApi\Response.
 *
 * @Annotation
 */
class OpenlegApiResponse extends Plugin {

  /**
   * The plugin ID.  This should match the Openleg responseType property.
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

}
