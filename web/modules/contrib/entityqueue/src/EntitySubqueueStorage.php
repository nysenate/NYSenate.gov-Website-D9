<?php

namespace Drupal\entityqueue;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for entity subqueues.
 */
class EntitySubqueueStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function restore(EntityInterface $entity) {
    // Since subqueues have string IDs, the revision ID can not be set
    // automatically during a schema conversion.
    $revision_id = $this->database->select($this->getTableMapping()->getRevisionTable())->countQuery()->execute()->fetchField() + 1;
    $entity->set($this->revisionKey, $revision_id);

    parent::restore($entity);
  }

}
