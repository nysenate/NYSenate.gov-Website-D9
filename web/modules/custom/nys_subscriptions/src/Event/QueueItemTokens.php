<?php

namespace Drupal\nys_subscriptions\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\nys_subscriptions\SubscriptionQueueItem;

/**
 * Defines the nys_subscriptions.queueitem.tokens event.
 *
 * This event will be dispatched as a queue item is initialized for processing.
 * It should be used to populate substitutions common to every subscriber.
 */
class QueueItemTokens extends Event {

  /**
   * The queue item being processed.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionQueueItem
   */
  public SubscriptionQueueItem $item;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_subscriptions\SubscriptionQueueItem $item
   *   The queue item needing references.
   */
  public function __construct(SubscriptionQueueItem $item) {
    $this->item = $item;
  }

}
