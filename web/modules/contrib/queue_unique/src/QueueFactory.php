<?php

namespace Drupal\queue_unique;

use Drupal\Core\Queue\QueueFactory as CoreQueueFactory;

/**
 * Defines the queue factory supporting queue_unique.
 */
class QueueFactory extends CoreQueueFactory {

  /**
   * Constructs a new queue. A name prefix can be used to find a queue factory.
   *
   * @param string $name
   *   The name of the queue to work with. If the name has a prefix ending with
   *   a "/" and a service exists with that prefix plus "database", we use
   *   that service to provide the queue by default. For example, if $name is
   *   "queue_unique/mymodule_work" we default to service
   *   "queue_unique.database" and queue name "mymodule_work". Settings for
   *   the specific queue service name and queue_default are used in preference
   *   to this default service name.
   * @param bool $reliable
   *   (optional) TRUE if the ordering of items and guaranteeing every item
   *   executes at least once is important, FALSE if scalability is the main
   *   concern. Defaults to FALSE.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   A queue implementation for the given name.
   */
  public function get($name, $reliable = FALSE) {
    if (!isset($this->queues[$name])) {
      $service_name = NULL;
      $queue_name = $name;
      // If it is a reliable queue, check the specific settings first. This is
      // the same as the core factory.
      if ($reliable) {
        $service_name = $this->settings->get('queue_reliable_service_' . $name);
      }
      // If no reliable queue was defined, check the service and global
      // settings, then fall back to the default service name.
      if (empty($service_name)) {
        $service_name = $this->settings->get('queue_service_' . $name);
      }
      if (empty($service_name)) {
        [$default_service_name, $queue_name] = $this->defaultServiceAndQueueName($name);
        $service_name = $this->settings->get('queue_default', $default_service_name);
      }
      $this->queues[$name] = $this->container->get($service_name)->get($queue_name);
    }
    return $this->queues[$name];
  }

  /**
   * Build the default service name and final queue name from the name.
   *
   * @param string $name
   *   The name of the queue to work with.
   *
   * @return array
   *   The default service name and the queue name.
   */
  public function defaultServiceAndQueueName(string $name): array {
    $default_service_name = 'queue.database';
    // Check for a specific prefix. For example, "queue_unique/mymodule_work".
    $slash_pos = strpos($name, '/');
    if ($slash_pos) {
      $prefix = substr($name, 0, $slash_pos);
      $test_name = "$prefix.database";
      if ($this->container->has($test_name)) {
        // If the name was "queue_unique/mymodule_work" we end with service
        // "queue_unique.database" and queue name "mymodule_work".
        $default_service_name = $test_name;
        $name = substr($name, $slash_pos + 1);
      }
    }
    return [$default_service_name, $name];
  }

}
