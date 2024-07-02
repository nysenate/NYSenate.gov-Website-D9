<?php

namespace Drupal\node_revision_delete\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for node revision delete plugins.
 */
interface NodeRevisionDeleteInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Determines which revisions are allowed to be deleted.
   *
   * @param array $revision_ids
   *   The revision IDs to check.
   * @param int $active_vid
   *   The active revision ID.
   *
   * @return array
   *   Returns an array keyed by revision IDs. The value for a revision should
   *   be one of the following:
   *   - TRUE: The revision should be deleted according the plugin.
   *   - FALSE: The revision must be kept according the plugin.
   *   - NULL: The plugin has no opinion on whether to keep or delete the
   *     revision.
   */
  public function checkRevisions(array $revision_ids, int $active_vid): array;

}
