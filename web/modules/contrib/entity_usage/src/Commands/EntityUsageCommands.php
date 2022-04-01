<?php

namespace Drupal\entity_usage\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_usage\EntityUsageQueueBatchManager;
use Drush\Commands\DrushCommands;
use Drupal\entity_usage\EntityUsageBatchManager;

/**
 * Entity Usage drush commands.
 */
class EntityUsageCommands extends DrushCommands {

  /**
   * The Entity Usage batch manager.
   *
   * @var \Drupal\entity_usage\EntityUsageBatchManager
   */
  protected $batchManager;

  /**
   * The Entity Usage queue batch manager.
   *
   * @var \Drupal\entity_usage\EntityUsageQueueBatchManager
   */
  protected $queueBatchManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity usage configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $entityUsageConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityUsageBatchManager $batch_manager, EntityUsageQueueBatchManager $queue_batch_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->batchManager = $batch_manager;
    $this->queueBatchManager = $queue_batch_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsageConfig = $config_factory->get('entity_usage.settings');
  }

  /**
   * Recreate all entity usage statistics.
   *
   * @command entity-usage:recreate
   * @aliases eu-r,entity-usage-recreate
   * @option use-queue
   *   Use a queue instead of a batch process to recreate tracking info. This
   *   means usage information won't be accurate until all items in the queue
   *   have been processed by cron runs.
   * @option batch-size
   *   When --use-queue is used, the queue will be populated in a batch process
   *   to avoid memory issues. The --batch-size flag can be optionally used to
   *   specify the batch size, for example --batch-size=500.
   */
  public function recreate($options = ['use-queue' => FALSE, 'batch-size' => 0]) {
    if (!empty($options['batch-size']) && empty($options['use-queue'])) {
      $this->output()->writeln(t('The --batch-size option can only be used when the --use-queue flag is specified. Aborting.'));
      return;
    }
    if (!empty($options['use-queue'])) {
      $this->queueBatchManager->populateQueue($options['batch-size']);
      drush_backend_batch_process();
    }
    else {
      $this->batchManager->recreate();
      drush_backend_batch_process();
    }
  }

}
