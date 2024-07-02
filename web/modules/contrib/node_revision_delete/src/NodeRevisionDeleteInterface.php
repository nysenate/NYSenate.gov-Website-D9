<?php

namespace Drupal\node_revision_delete;

/**
 * The Node Revision Delete Interface.
 *
 * @package Drupal\node_revision_delete
 */
interface NodeRevisionDeleteInterface {

  /**
   * Get all revision that are older than current deleted revision.
   *
   * The revisions should have the same language as the current language of the
   * page.
   *
   * @param int $nid
   *   The node id.
   * @param int $currently_deleted_revision_id
   *   The current revision.
   *
   * @return array
   *   An array with the previous revisions.
   */
  public function getPreviousRevisions(int $nid, int $currently_deleted_revision_id): array;

  /**
   * Check if a node exists in the queue.
   *
   * @param int $nid
   *   The node id.
   *
   * @return int
   *   The queue item id if exists, 0 otherwise.
   */
  public function nodeExistsInQueue(int $nid): int;

  /**
   * Force the deletion of a specified queue item.
   *
   * @param int $item_id
   *   The item id to be deleted.
   */
  public function deleteItemFromQueue(int $item_id): void;

  /**
   * Check if a content type has at least one plugin enabled.
   *
   * @param string $content_type_id
   *   The content type ID.
   *
   * @return bool
   *   TRUE if the content type has at least one plugin enabled.
   */
  public function contentTypeHasEnabledPlugins(string $content_type_id): bool;

}
