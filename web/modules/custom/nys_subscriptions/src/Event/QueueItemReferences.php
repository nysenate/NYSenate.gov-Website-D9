<?php

namespace Drupal\nys_subscriptions\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_subscriptions\SubscriptionQueueItem;

/**
 * Defines the nys_subscriptions.queueitem.references event.
 *
 * This event will be dispatched during the constructor of the queue item
 * being instantiated.
 */
class QueueItemReferences extends Event {

  /**
   * The queue item being instantiated.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionQueueItem
   */
  public SubscriptionQueueItem $item;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_subscriptions\SubscriptionQueueItem $item
   *   The queue item needing references.
   */
  public function __construct(SubscriptionQueueItem $item) {
    $this->item = $item;
    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

}
