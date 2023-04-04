<?php

namespace Drupal\nys_senators\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Describes plugins for pages on the senator management dashboard.
 *
 * Plugin Namespace: Plugin\NysDashboard.
 *
 * @Annotation
 */
class SenatorManagementPage extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * A map of names to their handling classes/methods.
   *
   * @var array
   */
  public array $handlers = [];

}
