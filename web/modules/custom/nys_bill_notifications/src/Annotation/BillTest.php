<?php

namespace Drupal\nys_bill_notifications\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an annotation object for bill test plugins.
 *
 * Plugin Namespace: Plugin\BillNotifications\BillTests.
 *
 * @Annotation
 */
class BillTest extends Plugin {

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
   * The canonical test name known to NYS.
   *
   * @var string
   */
  public string $name;

  /**
   * The test pattern.
   *
   * @var array
   */
  public array $pattern;

  /**
   * A priority level.
   *
   * @var int
   */
  public int $priority = 0;

  /**
   * Indicates if the test is disabled.
   *
   * @var bool
   */
  public bool $disabled = FALSE;

}
