<?php

namespace Drupal\queue_unique\Plugin\QueueUI;

use Drupal\queue_ui\Plugin\QueueUI\DatabaseQueue;

/**
 * Defines a Drupal Queue UI backend for the queue_unique module.
 *
 * @QueueUI(
 *   id = "unique_database_queue",
 *   class_name = "UniqueDatabaseQueue"
 * )
 */
class UniqueDatabaseQueue extends DatabaseQueue {

    public const TABLE_NAME = \Drupal\queue_unique\UniqueDatabaseQueue::TABLE_NAME;

}
