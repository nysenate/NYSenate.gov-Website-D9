<?php

namespace Drupal\entityqueue;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class EntityQueueRepository.
 */
class EntityQueueRepository implements EntityQueueRepositoryInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityQueueRepository object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableQueuesForEntity(EntityInterface $entity) {
    $storage = $this->entityTypeManager->getStorage('entity_queue');

    $queue_ids = $storage->getQuery()
      ->condition('entity_settings.target_type', $entity->getEntityTypeId(), '=')
      ->condition('status', TRUE)
      ->execute();

    $queues = $storage->loadMultiple($queue_ids);
    $queues = array_filter($queues, function ($queue) use ($entity) {
      /** @var \Drupal\entityqueue\EntityQueueInterface $queue */
      $queue_settings = $queue->getEntitySettings();
      $target_bundles = &$queue_settings['handler_settings']['target_bundles'];
      return ($target_bundles === NULL || in_array($entity->bundle(), $target_bundles, TRUE));
    });

    return $queues;
  }

}
