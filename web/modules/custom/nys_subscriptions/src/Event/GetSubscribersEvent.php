<?php

namespace Drupal\nys_subscriptions\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the nys_subscriptions.get.subscribers event.
 *
 * This event will be dispatched after loading all subscribers for an entity.
 */
class GetSubscribersEvent extends Event {

  /**
   * A Drupal entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public EntityInterface $entity;

  /**
   * An array of Subscription entities.
   *
   * @var array
   */
  public array $subscribers;

  /**
   * The selection flag, as passed to getSubscribers().
   *
   * @var int
   *
   * @see \Drupal\nys_subscriptions\Entity\Subscription::getSubscribers()
   */
  public int $flags;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity owning the subscribers.
   * @param array $subscribers
   *   Array of Subscription entities.
   * @param int $flags
   *   A bit-flag value, as passed to Subscription::getSubscribers()
   *
   * @see \Drupal\nys_subscriptions\Entity\Subscription
   */
  public function __construct(EntityInterface $entity, array &$subscribers, int $flags) {
    $this->entity = $entity;
    $this->subscribers = $subscribers;
    $this->flags = $flags;
  }

}
