<?php

namespace Drupal\nys_subscriptions\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\nys_subscriptions\Subscriber;
use Drupal\nys_subscriptions\SubscriptionQueueItem;

/**
 * Defines the nys_subscriptions.queueitem.subscribers event.
 *
 * This event will be dispatched as each subscriber is added to the SendGrid
 * API request object during queue item processing.  It should be used to
 * populate substitutions specific to the subscriber.
 */
class QueueItemSubscribers extends Event {

  /**
   * The queue item being processed.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionQueueItem
   */
  public SubscriptionQueueItem $item;

  /**
   * The subscriber being processed.
   *
   * @var \Drupal\nys_subscriptions\Subscriber
   */
  public Subscriber $subscriber;

  /**
   * Constructor.
   *
   * @param \Drupal\nys_subscriptions\SubscriptionQueueItem $item
   *   The queue item being processed.
   * @param \Drupal\nys_subscriptions\Subscriber $subscriber
   *   The subscriber being processed.
   */
  public function __construct(SubscriptionQueueItem $item, Subscriber $subscriber) {
    $this->item = $item;
    $this->subscriber = $subscriber;
  }

}
