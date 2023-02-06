<?php

namespace Drupal\queue_unique;

use Drupal\Core\Queue\QueueDatabaseFactory;

/**
 * Factory class for generating unique database queues.
 */
class UniqueQueueDatabaseFactory extends QueueDatabaseFactory {

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return new UniqueDatabaseQueue($name, $this->connection);
  }

}
