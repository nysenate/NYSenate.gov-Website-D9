<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\scheduler\EventBase;

/**
 * An existing node is scheduled for publishing.
 *
 * This event is fired when an existing node is updated/saved and it has a
 * scheduled publishing date.
 */
class ExistingNodeIsScheduledForPublishingEvent extends EventBase {

  const EVENT_NAME = 'scheduler_existing_node_is_scheduled_for_publishing_event';

}
