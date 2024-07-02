<?php

namespace Drupal\entityqueue;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic permissions for the Entityqueue module.
 */
class EntityQueuePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of queue permissions.
   *
   * @return array
   */
  public function permissions() {
    $perms = [];
    // Generate queue permissions for all queues.
    foreach (EntityQueue::loadMultiple() as $queue) {
      $perms += $this->buildPermissions($queue);
    }

    return $perms;
  }

  /**
   * Returns a set of permissions for a specific queue.
   *
   * @param \Drupal\entityqueue\Entity\EntityQueue $queue
   *   An EntityQueue entity.
   *
   * @return array
   */
  public function buildPermissions(EntityQueue $queue) {
    $queue_id = $queue->id();

    if ($queue->getHandlerPlugin()->supportsMultipleSubqueues()) {
      $permissions["create $queue_id entityqueue"] = [
        'title' => $this->t('Add %queue subqueues', ['%queue' => $queue->label()]),
        'description' => $this->t('Access to create new subqueue to the %queue queue.', ['%queue' => $queue->label()]),
        'dependencies' => [$queue->getConfigDependencyKey() => [$queue->getConfigDependencyName()]],
      ];
      $permissions["delete $queue_id entityqueue"] = [
        'title' => $this->t('Delete %queue subqueues', ['%queue' => $queue->label()]),
        'description' => $this->t('Access to delete subqueues of the %queue queue.', ['%queue' => $queue->label()]),
        'dependencies' => [$queue->getConfigDependencyKey() => [$queue->getConfigDependencyName()]],
      ];
    }

    $permissions["update $queue_id entityqueue"] = [
      'title' => $this->t('Manipulate %queue queue', ['%queue' => $queue->label()]),
      'description' => $this->t('Access to update the %queue queue.', ['%queue' => $queue->label()]),
      'dependencies' => [$queue->getConfigDependencyKey() => [$queue->getConfigDependencyName()]],
    ];

    return $permissions;
  }

}
