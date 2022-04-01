<?php

namespace Drupal\scheduler;

/**
 * Contains all events dispatched by Scheduler.
 *
 * Ideally the namespace should have been Drupal\scheduler\Event and all the
 * event-related files stored in a src/Event folder. This cannot be chnaged now
 * as it would break the API which is being used by 3rd-party modules
 * subscribing to scheduler's events.
 */
final class SchedulerEvents {

  /**
   * The event triggered after a node is published immediately.
   *
   * This event allows modules to react after a node is published immediately.
   * The event listener method receives a \Drupal\Core\Entity\EntityInterface
   * instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEvent
   *
   * @var string
   */
  const PUBLISH_IMMEDIATELY = 'scheduler.publish_immediately';

  /**
   * The event triggered after a node is published via cron.
   *
   * This event allows modules to react after a node is published. The event
   * listener method receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEvent
   *
   * @var string
   */
  const PUBLISH = 'scheduler.publish';

  /**
   * The event triggered before a node is published immediately.
   *
   * This event allows modules to react before a node is published immediately.
   * The event listener method receives a \Drupal\Core\Entity\EntityInterface
   * instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEvent
   *
   * @var string
   */
  const PRE_PUBLISH_IMMEDIATELY = 'scheduler.pre_publish_immediately';

  /**
   * The event triggered before a node is published via cron.
   *
   * This event allows modules to react before a node is published. The event
   * listener method receives a \Drupal\Core\Entity\EntityInterface
   * instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEvent
   *
   * @var string
   */
  const PRE_PUBLISH = 'scheduler.pre_publish';

  /**
   * The event triggered before a node is unpublished via cron.
   *
   * This event allows modules to react before a node is unpublished. The
   * event listener method receives a \Drupal\Core\Entity\EntityInterface
   * instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEvent
   *
   * @var string
   */
  const PRE_UNPUBLISH = 'scheduler.pre_unpublish';

  /**
   * The event triggered after a node is unpublished via cron.
   *
   * This event allows modules to react after a node is unpublished. The event
   * listener method receives a \Drupal\Core\Entity\EntityInterface instance.
   *
   * @Event
   *
   * @see \Drupal\scheduler\SchedulerEvent
   *
   * @var string
   */
  const UNPUBLISH = 'scheduler.unpublish';

}
