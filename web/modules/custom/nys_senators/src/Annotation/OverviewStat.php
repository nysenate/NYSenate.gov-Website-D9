<?php

namespace Drupal\nys_senators\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for overview stat plugins.
 *
 * Plugin Namespace: Plugin\NysDashboard.
 *
 * @Annotation
 */
class OverviewStat extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the test.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * A short description of the test.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The URL to use as the link's href attribute. (Optional)
   *
   * @var string
   */
  public string $url = '';

  /**
   * An array of classes to the stat's container.
   *
   * @var array
   */
  public array $classes = [];

  /**
   * Used to order the blocks.  Lower numbers appear first.
   *
   * @var int
   */
  public int $weight = 0;

}
