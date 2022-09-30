<?php

namespace Drupal\nys_subscriptions;

/**
 * Defines events for nys_subscriptions module.
 */
final class Events {

  /**
   * Name of the event which fires when loading an entity's subscribers.
   */
  const GET_SUBSCRIBERS = 'nys_subscriptions.get.subscribers';

  /**
   * Event that fires as a queue item is being instantiated.
   */
  const QUEUEITEM_REFERENCES = 'nys_subscriptions.queueitem.references';

  /**
   * Event that fires as a queue item is being instantiated.
   */
  const QUEUEITEM_TOKENS = 'nys_subscriptions.queueitem.tokens';

  /**
   * Event that fires as a queue item is being instantiated.
   */
  const QUEUEITEM_SUBSCRIBERS = 'nys_subscriptions.queueitem.subscribers';

}
