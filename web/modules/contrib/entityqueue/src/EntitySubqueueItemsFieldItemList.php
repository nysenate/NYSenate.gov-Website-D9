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
    // If the queue is reversed, new items should be be added to the top of the
    // queue.
    if ($this->getEntity()->getQueue()->isReversed()) {
      $item = $this->createItem(0, $value);
      array_unshift($this->list, $item);
      return $item;
    }

    return parent::appendItem($value);
  }

}
