<?php

namespace Drupal\nys_accumulator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation object for accumulator event info generators.
 *
 * Plugin Namespace: Plugin\Accumulator.
 *
 * @Annotation
 */
class EventInfoGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * An array of "entity_type:bundle" values allowed for the plugin source.
   *
   * @var array
   */
  public array $requires;

  /**
   * Used as a prefix to the source id() for the 'content_url' key.
   *
   * @var string
   */
  public string $content_url = '';

  /**
   * Indexed by event info key, with values being the source field name.
   *
   * @var array
   */
  public array $fields = [];

}
