<?php

/**
 * @file
 * Class alias for Drupal\scheduler\SchedulerEvent.
 */

/**
 * Create event class alias to maintain backwards-compatibility.
 *
 * The original event classes, named Drupal\scheduler\SchedulerEvent and
 * Drupal\scheduler\SchedulerEvents must remain for backwards-compatibility
 * with existing implementations of event subscribers for Node events. The
 * namespace should have been Drupal\scheduler\Event and all the event-related
 * files stored in a src/Event folder, but instead they were just in /src.
 *
 * Now that Scheduler supports non-node entities and each type has to have its
 * own specific event class named 'Scheduler{Type}Events', they can be moved
 * into a Drupal\scheduler\Event namespace, with all event files being stored in
 * a src/Event folder. These two aliases, for the original node events, ensure
 * that any existing event subscribers will continue work unchnaged.
 */

class_alias('Drupal\scheduler\Event\SchedulerEvent', 'Drupal\scheduler\SchedulerEvent');
