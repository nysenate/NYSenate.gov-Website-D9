<?php

namespace Drupal\node_revision_delete;

use Drupal\node\Entity\Node;

/**
 * Methods for delete revisions in a batch.
 */
class NodeRevisionDeleteBatch {

  /**
   * Delete revision.
   *
   * Once the revision is deleted the context is updated with the total number
   * of revisions deleted and the node object.
   *
   * @param \Drupal\node\Entity\Node|int $revision
   *   The revision to delete.
   * @param bool $dry_run
   *   Indicate if we need to delete or not the revision. TRUE for test purpose
   *   FALSE to delete the revision.
   * @param mixed $context
   *   The context of the current batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function deleteRevision($revision, $dry_run, &$context) {
    if (empty($context['results'])) {
      $context['results']['revisions'] = 0;

      if ($revision instanceof Node) {
        // Update context of the current node.
        $context['results']['node'] = $revision;
      }
    }

    if ($revision instanceof Node) {
      $revision = $revision->getRevisionId();
    }

    // Checking if this is a dry run or we really need to delete the variable.
    if (!$dry_run) {
      // Delete the revision.
      \Drupal::entityTypeManager()->getStorage('node')->deleteRevision($revision);
    }

    // Count the number of revisions deleted.
    $context['results']['revisions']++;
    // Adding a message for the actual revision being deleted.
    $context['message'] = t('Processing revision: @id', ['@id' => $revision]);
  }

  /**
   * Actions on finishing the batch.
   *
   * @param bool $success
   *   The flag to identify if batch has successfully run or not.
   * @param array $results
   *   The results from running context.
   * @param array $operations
   *   The array of operations remained unprocessed.
   */
  public static function finish($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    $logger = \Drupal::logger('node_revision_delete');

    if ($success) {
      // If we are finishing the prior delete feature.
      if (isset($context['results']['node'])) {
        $variables = [
          '@total' => $results['revisions'],
          '@type' => $results['node']->type->entity->label(),
          '@title' => $results['node']->label(),
        ];

        $success_message = \Drupal::translation()->formatPlural(
          $results['revisions'],
          'One prior revision deleted.',
          '@count prior revisions deleted of @type <em>@title</em>',
          $variables
        );
      }
      else {
        $success_message = \Drupal::translation()->formatPlural(
          $results['revisions'],
          'One revision has been deleted.',
          'Deleted @count revisions.',
          ['@total' => $results['revisions']]
        );
      }

      $logger->notice($success_message);
      $messenger->addMessage($success_message);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $logger->error($message);
      $messenger->addError($message);
    }
  }

}
