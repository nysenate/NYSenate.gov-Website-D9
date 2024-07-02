<?php

namespace Drupal\node_revision_delete;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Batch controller to process node revision delete.
 *
 * Class NodeRevisionDeleteBatch declaration.
 */
class NodeRevisionDeleteBatch implements NodeRevisionDeleteBatchInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The Entity Type Manager Service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Queue Service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queue;

  /**
   * The Logger Factory Service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * The Node Revision Delete Service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected NodeRevisionDeleteInterface $nodeRevisionDelete;

  /**
   * Constructor of the Node Revision Delete Batch service.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger Factory.
   * @param \Drupal\node_revision_delete\NodeRevisionDeleteInterface $node_revision_delete
   *   The node revision delete.
   */
  public function __construct(
    MessengerInterface $messenger,
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue,
    LoggerChannelFactoryInterface $logger_factory,
    NodeRevisionDeleteInterface $node_revision_delete
  ) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue;
    $this->logger = $logger_factory;
    $this->nodeRevisionDelete = $node_revision_delete;
  }

  /**
   * {@inheritdoc}
   */
  public function queueBatch(): void {
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Creating queue items'))
      ->setInitMessage($this->t('Starting to add nodes.'))
      ->setProgressMessage($this->t('Processing node @current out of @total (@percentage%). Estimated time: @estimate.'))
      ->setErrorMessage($this->t('Error adding nodes.'))
      ->setFinishCallback([$this, 'finishQueue']);

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $node_type) {
      if ($this->nodeRevisionDelete->contentTypeHasEnabledPlugins($node_type->id())) {
        // Get all node IDs for this node type.
        $nids = $this->entityTypeManager->getStorage('node')->getQuery()
          ->condition('type', $node_type->id())
          ->accessCheck(FALSE)
          ->execute();

        foreach ($nids as $nid) {
          if (!$this->nodeRevisionDelete->nodeExistsInQueue($nid)) {
            // Create a queue for all nodes in this content type.
            $batch->addOperation(
              [$this, 'queue'],
              [$node_type, $nid]);
          }
        }
      }
    }

    batch_set($batch->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function queue(NodeTypeInterface $node_type, int $nid, &$context): void {
    if (empty($context['results']['total'])) {
      $context['results']['total'] = 0;
    }

    // Adding the node to the queue.
    $this->queue->get('node_revision_delete')->createItem($nid);
    $context['results']['total']++;

    // Adding a message for the actual node being added.
    $message = $this->t('Node id @nid of @node_type_label [@node_type_id] has been added to the queue.', [
      '@nid' => $nid,
      '@node_type_id' => $node_type->id(),
      '@node_type_label' => $node_type->label(),
    ]);
    $context['message'] = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function finishQueue(bool $success, array $results, array $operations): void {
    $messenger = $this->messenger;
    $logger = $this->logger->get('node_revision_delete');

    if ($success) {
      if (!empty($results['total'])) {
        $success_message = $this->formatPlural(
          $results['total'],
          'One node has been added to the queue.',
          '@count nodes has been added to the queue.',
        );

        $logger->notice($success_message);
        $messenger->addMessage($success_message);
      }
      else {
        $warning_message = $this->t('No nodes has been added to the queue. Possible reasons are: All the nodes are in the queue, or you do not have any nodes on your site, or all content types have their node revision delete settings disabled.');
        $logger->notice($warning_message);
        $messenger->addWarning($warning_message);
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $error_message = $this->t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $logger->error($error_message);
      $messenger->addError($error_message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function previousRevisionDeletionBatch(int $nid, int $currently_deleted_revision_id): void {
    // Defining the batch builder.
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Deleting revisions'))
      ->setInitMessage($this->t('Starting to delete revisions.'))
      ->setProgressMessage($this->t('Deleted @current out of @total (@percentage%). Estimated time: @estimate.'))
      ->setErrorMessage($this->t('Error deleting revisions.'))
      ->setFinishCallback([$this, 'finishPreviousRevisions']);

    // Get list of revisions older than current revision.
    $revisions = $this->nodeRevisionDelete->getPreviousRevisions($nid, $currently_deleted_revision_id);

    $total = count($revisions);

    // Loop through the revisions to delete, create batch operations array.
    foreach ($revisions as $revision) {
      // Adding the operation.
      $batch->addOperation(
        [$this, 'deletePreviousRevision'],
        [$revision, $total]
      );
    }

    batch_set($batch->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function deletePreviousRevision(NodeInterface $revision, int $total, &$context): void {
    if (empty($context['results'])) {
      $context['results']['revisions'] = 0;
      $context['results']['node'] = $revision;
    }

    $vid = $revision->getRevisionId();
    // Delete the revision.
    $this->entityTypeManager->getStorage('node')->deleteRevision($vid);

    // Count the number of revisions deleted.
    $context['results']['revisions']++;
    // Adding a message for the actual revision being deleted.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $context['results']['node'];
    $message = $this->t('Revision @vid [nid: @nid] of @type <em>@title</em> [@lang] has been deleted.', [
      '@vid' => $vid,
      '@nid' => $node->id(),
      '@type' => $node->type->entity->label(),
      '@title' => $node->label(),
      '@lang' => $node->language()->getId(),
    ]);
    $context['message'] = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function finishPreviousRevisions(bool $success, array $results, array $operations): void {
    $messenger = $this->messenger;
    $logger = $this->logger->get('node_revision_delete');

    if ($success) {
      $variables = [
        '@total' => $results['revisions'],
        '@type' => $results['node']->type->entity->label(),
        '@title' => $results['node']->label(),
      ];

      $success_message = $this->formatPlural(
        $results['revisions'],
        'One prior revision of @type <em>@title</em>. has been deleted.',
        '@count prior revisions of @type <em>@title</em> has been deleted.',
        $variables
      );

      $logger->notice($success_message);
      $messenger->addMessage($success_message);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $error_message = $this->t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $logger->error($error_message);
      $messenger->addError($error_message);
    }
  }

}
