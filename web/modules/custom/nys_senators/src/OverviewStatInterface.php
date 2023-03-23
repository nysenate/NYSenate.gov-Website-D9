<?php

namespace Drupal\nys_senators;

use Drupal\taxonomy\TermInterface;

/**
 * For OverviewStat plugins.
 */
interface OverviewStatInterface {

  /**
   * Gets the plugin definition.
   */
  public function getDefinition(): array;

  /**
   * Getter for the stat content (HTML, plain text, blank string).
   */
  public function getContent(TermInterface $senator): string;

}
