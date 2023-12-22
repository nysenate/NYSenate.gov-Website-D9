<?php

namespace Drupal\nys_openleg_api\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for Openleg request plugins.
 *
 * Plugin Namespace: Plugin\OpenlegApi\Request.
 *
 * @Annotation
 */
class OpenlegApiRequest extends Plugin {

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
   * A short description of the mail plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The base endpoint used for requests.
   *
   * @var string
   */
  public string $endpoint;

}
