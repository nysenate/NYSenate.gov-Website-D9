<?php

namespace Drupal\entityqueue;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface EntityQueueRepositoryInterface.
 */
interface EntityQueueRepositoryInterface {

  /**
   * Gets a list of queues which can hold this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return \Drupal\entityqueue\EntityQueueInterface[]
   *   An array of entity queues which can hold this entity.
   */
  public function getAvailableQueuesForEntity(EntityInterface $entity);

}
