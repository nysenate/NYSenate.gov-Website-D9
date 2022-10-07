<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Queue\QueueInterface;

/**
 * A wrapper interface to expose the queue name.
 */
interface SubscriptionQueueInterface extends QueueInterface {

  /**
   * Exposes the queue name.
   */
  public function getName(): string;

}
