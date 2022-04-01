<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\scheduler\EventBase;

/**
 * A node is unpublished by Scheduler.
 *
 * This event is fired when Scheduler unpublishes a node via cron.
 */
class SchedulerHasUnpublishedThisNodeEvent extends EventBase {

  const EVENT_NAME = 'scheduler_has_unpublished_this_node_event';

}
