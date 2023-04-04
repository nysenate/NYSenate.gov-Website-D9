<?php

namespace Drupal\nys_senators;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\TermInterface;

/**
 * For OverviewStat plugins.
 */
interface OverviewStatInterface extends ContainerFactoryPluginInterface {

  /**
   * Gets the plugin definition.
   */
  public function getDefinition(): array;

  /**
   * Getter for the stat's content (HTML, plain text, blank string).
   *
   * Must return NULL if the stat block should not be rendered.
   */
  public function getContent(TermInterface $senator): ?string;

}
