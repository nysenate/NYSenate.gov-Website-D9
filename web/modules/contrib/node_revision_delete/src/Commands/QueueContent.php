<?php

namespace Drupal\node_revision_delete\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node_revision_delete\NodeRevisionDeleteInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush command for putting all content in a queue.
 */
class QueueContent extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The NodeRevisionDelete service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected NodeRevisionDeleteInterface $nodeRevisionDelete;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queue;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\node_revision_delete\NodeRevisionDeleteInterface $node_revision_delete
   *   The node revision delete service.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, NodeRevisionDeleteInterface $node_revision_delete, QueueFactory $queue) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeRevisionDelete = $node_revision_delete;
    $this->queue = $queue;
  }

  /**
   * Validate for node-revision-delete:queue.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data.
   *
   * @hook validate node-revision-delete:queue
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validateQueueContent(CommandData $commandData) {
    // Getting the content types.
    $content_types = $commandData->input()->getOption('type');
    if (!empty($content_types)) {
      $content_types = explode(',', $content_types);

      $content_types_database = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      // Creating an array with all content types.
      $content_types_list = [];
      foreach ($content_types_database as $content_type) {
        $content_types_list[] = $content_type->id();
      }

      $invalid_content_types = array_diff($content_types, $content_types_list);

      if (count($invalid_content_types)) {
        $names = implode(', ', $invalid_content_types);
        throw new \Exception(dt('Invalid content types names: @names.', ['@names' => $names]));
      }
    }
  }

  /**
   * Creates queue items or all content.
   *
   * This creates queue items for all content which then can be processed
   * during cron.
   *
   * @option type A comma-separated list of content types to process. If not provided, all content types will be processed.
   *
   * @usage drush node-revision-delete:queue
   *   Creates queue items for all content where settings apply.
   * @usage drush node-revision-delete:queue --type=article,page
   *   Creates queue items for mentioned content types.
   *
   * @command node-revision-delete:queue
   *
   * @aliases nrd-q, nrd-queue
   */
  public function queueContent($options = ['type' => '']): void {
    if (!empty($options['type'])) {
      $content_types = explode(',', $options['type']);
      $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple($content_types);
    }
    else {
      $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    }

    foreach ($content_types as $content_type) {
      // Check whether at least one plugin is enabled for this content type.
      $has_enabled_plugins = $this->nodeRevisionDelete->contentTypeHasEnabledPlugins($content_type->id());
      if ($has_enabled_plugins) {
        // Create a queue for all nodes in this content type.
        $this->createQueue($content_type->id());
      }
    }
  }

  /**
   * Create queue items for all nodes of a content type.
   *
   * @param string $node_type
   *   The content type machine name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createQueue(string $node_type): void {
    // Get all node IDs for this node type.
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', $node_type)
      ->accessCheck(FALSE)
      ->execute();
    $counter = 0;
    foreach ($nids as $nid) {
      $nid = (int) $nid;
      // Create queue item only if they dont exist.
      if (!$this->nodeRevisionDelete->nodeExistsInQueue($nid)) {
        $counter++;
        $this->queue->get('node_revision_delete')->createItem($nid);
      }
    }
    $this->output()->writeln(dt('<info>Created <comment>@count</comment> queue items for content-type: <comment>@node_type.</comment></info>', [
      '@count' => $counter,
      '@node_type' => $node_type,
    ]));
  }

}
