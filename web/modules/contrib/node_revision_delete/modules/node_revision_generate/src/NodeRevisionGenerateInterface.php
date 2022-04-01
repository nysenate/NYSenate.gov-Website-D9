<?php

namespace Drupal\node_revision_generate;

/**
 * Interface NodeRevisionGenerateInterface.
 *
 * @package Drupal\node_revision_generate
 */
interface NodeRevisionGenerateInterface {

  /**
   * Get the available nodes to generate revisions.
   *
   * Returns the ids of the available nodes to generate the revisions and the
   * next date (Unix timestamp) of the revision to be generated for that node.
   *
   * @param array $bundles
   *   An array with the selected content types to generate node revisions.
   * @param int $revisions_age
   *   Interval in Unix timestamp format to add to the last revision date of the
   *   node.
   *
   * @return array
   *   Returns the available nodes ids to generate the revisions and its next
   *   revision date.
   */
  public function getAvailableNodesForRevisions(array $bundles, $revisions_age);

  /**
   * Return the revision creation batch definition.
   *
   * @param array $nodes_for_revisions
   *   The nodes for revisions array.
   * @param int $revisions_number
   *   Number of revisions to generate.
   * @param int $revisions_age
   *   Interval in Unix timestamp format to add to the last revision date of the
   *   node.
   *
   * @return array
   *   The batch definition.
   */
  public function getRevisionCreationBatch(array $nodes_for_revisions, $revisions_number, $revisions_age);

  /**
   * Returns if exists nodes of a content type.
   *
   * @param string $content_type
   *   Content type machine name.
   *
   * @return bool
   *   If exists nodes or not for a content type.
   */
  public function existsNodesContentType($content_type);

}
