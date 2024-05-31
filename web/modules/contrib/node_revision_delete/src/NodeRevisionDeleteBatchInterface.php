<?php

namespace Drupal\node_revision_delete;

use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * An interface for batch controller to process node revision deletes.
 *
 * @package Drupal\node_revision_delete
 */
interface NodeRevisionDeleteBatchInterface {

  /**
   * Prepares and executes the plugin revision deletion batch.
   */
  public function queueBatch(): void;

  /**
   * Batch step definition to process the plugin revision deletion queue.
   *
   * Based on \Drupal\Core\Cron::processQueues().
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   * @param int $nid
   *   The node id.
   * @param array|\DrushBatchContext $context
   *   An associative array or DrushBatchContext.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function queue(NodeTypeInterface $node_type, int $nid, &$context): void;

  /**
   * Callback when finishing a plugin revision deletion batch job.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public function finishQueue(bool $success, array $results, array $operations): void;

  /**
   * Prepares and executes the previous revision deletion batch.
   *
   * @param int $nid
   *   The node id.
   * @param int $currently_deleted_revision_id
   *   The current revision.
   */
  public function previousRevisionDeletionBatch(int $nid, int $currently_deleted_revision_id): void;

  /**
   * Batch step definition to delete previous revisions.
   *
   * Once the revision is deleted the context is updated with the total number
   * of revisions deleted and the node object.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The revision to delete.
   * @param int $total
   *   The total number of items to be processed.
   * @param mixed $context
   *   The context of the current batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deletePreviousRevision(NodeInterface $revision, int $total, &$context): void;

  /**
   * Callback when finishing the batch of previous revisions.
   *
   * @param bool $success
   *   The flag to identify if batch has successfully run or not.
   * @param array $results
   *   The results from running context.
   * @param array $operations
   *   The array of operations remained unprocessed.
   */
  public function finishPreviousRevisions(bool $success, array $results, array $operations): void;

}
