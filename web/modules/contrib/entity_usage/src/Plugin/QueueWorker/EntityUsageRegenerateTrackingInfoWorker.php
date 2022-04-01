<?php

namespace Drupal\entity_usage\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_usage\EntityUpdateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RegenerateTrackingInfoWorker class.
 *
 * A worker plugin to consume items from "entity_usage_regenerate_queue"
 * and regenerate tracking info for each of them.
 *
 * @QueueWorker(
 *   id = "entity_usage_regenerate_queue",
 *   title = @Translation("Entity Usage Regenerate Tracking Queue"),
 *   cron = {"time" = 60}
 * )
 */
class EntityUsageRegenerateTrackingInfoWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Entity Usage update manager service.
   *
   * @var \Drupal\entity_usage\EntityUpdateManager
   */
  protected $entityUsageUpdateManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, EntityUpdateManager $track_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('entity_usage');
    $this->entityUsageUpdateManager = $track_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('entity_usage.entity_update_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // $data here is expected to contain
    // - 'entity_type': always present;
    // - 'entity_id': present when the entity isn't revisionable;
    // - 'entity_revision_id': present when the entity is revisionable;
    if (empty($data['entity_type']) ||
      (empty($data['entity_id']) && empty($data['entity_revision_id']))) {
      // Just skip this item.
      return;
    }
    $storage = $this->entityTypeManager->getStorage($data['entity_type']);
    if ($storage->getEntityType()->isRevisionable() && !empty($data['entity_revision_id'])) {
      $entity = $storage->loadRevision($data['entity_revision_id']);
    }
    elseif (!empty($data['entity_id'])) {
      $entity = $storage->load($data['entity_id']);
    }

    if ($entity) {
      try {
        $this->entityUsageUpdateManager->trackUpdateOnCreation($entity);
      }
      catch (\Exception $e) {
        $this->logger->warning("An error occurred when tracking usage info for entity with data: @data. Error message: {$e->getMessage()}", ['@data' => json_encode($data)]);
      }
    }
  }

}
