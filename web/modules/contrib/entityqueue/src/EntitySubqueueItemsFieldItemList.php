<?php

namespace Drupal\entityqueue;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Defines an item list class for a subqueue's items.
 */
class EntitySubqueueItemsFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function appendItem($value = NULL) {
    // Get the queue associated with the entity.
    $queue = $this->getEntity()->getQueue();

    // Check if the queue is available and is reversed.
    if ($queue && $queue->isReversed()) {
      // If the queue is reversed, new items should be added to the top of the queue.
      $item = $this->createItem(0, $value);
      array_unshift($this->list, $item);
      return $item;
    }

    // If the queue is not reversed or not available, use parent implementation.
    return parent::appendItem($value);
  }

}
