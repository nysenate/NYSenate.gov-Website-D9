<?php

namespace Drupal\entity_usage\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_usage\EntityUpdateManager;
use Drupal\entity_usage\EntityUpdateManagerInterface;
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
   * @var \Drupal\entity_usage\EntityUpdateManagerInterface
   */
  protected $entityUsageUpdateManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, EntityUpdateManagerInterface $track_manager) {
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
   *
   * @param array{entity_type: string, entity_id?: string, entity_revision_id?: string} $data
   *   The entity to process. Entity type ID is always present, while either
   *   entity ID or revision ID is present depending on whether the entity is
   *   revisionable.
   */
  public function processItem($data) {
    [
      'entity_type' => $entityTypeId,
      'entity_id' => $id,
      'entity_revision_id' => $revisionId,
    ] = $data + [
      'entity_id' => NULL,
      'entity_revision_id' => NULL,
    ];

    $entity = NULL;
    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    if ($storage->getEntityType()->isRevisionable() && $revisionId) {
      $entity = $storage->loadRevision($revisionId);
    }
    elseif ($id) {
      $entity = $storage->load($id);
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
