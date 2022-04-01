<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\scheduler\EventBase;

/**
 * A node is published by Scheduler.
 *
 * This event is fired when Scheduler publishes a node via cron.
 */
class SchedulerHasPublishedThisNodeEvent extends EventBase {

  const EVENT_NAME = 'scheduler_has_published_this_node_event';

}
