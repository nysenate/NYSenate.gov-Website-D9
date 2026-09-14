<?php

namespace Drupal\nys_subscriptions\DependencyInjection\Compiler;

use Drupal\nys_subscriptions\SubscriptionQueue;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Supports lazy loading of subscription queue services.
 *
 * The service_collector tag actually instantiates the services it collects.
 * The queue services use SubscriptionQueueManager as a factory, but the
 * factory is not "ready" until service collection is complete.  This compiler
 * pass will create a map of defined queues and set it as a parameter on the
 * queue manager's definition, allowing the manager to be aware of the queues
 * (and their attributes) without instantiation.
 *
 * In turn, queues must include the subscription_queue tag, along with a
 * globally-unique queue_name.  It can optionally include queue_subject.
 *
 * The map is an array keyed by <queue_name>.  Each value is an array with
 * keys for 'service_id' (string), 'subject' (string), and 'class' (string,
 * FQDN classname).
 */
class SubscriptionQueueCollector implements CompilerPassInterface {

  /**
   * {@inheritDoc}
   *
   * Have to use trigger_error() because the container's log facilities may
   * not be built yet.
   */
  public function process(ContainerBuilder $container): void {
    // Make sure the queue manager service exists.  If not, nothing to do.
    $manager = $container->hasDefinition('nys_subscriptions.queue_manager')
      ? $container->getDefinition('nys_subscriptions.queue_manager')
      : NULL;
    if (!$manager) {
      return;
    }

    // All queue services need to be tagged with 'subscription_queue'.
    $queues = $container->findTaggedServiceIds('subscription_queue');
    $map = [];

    // Extract the map for each queue.
    foreach ($queues as $queue_id => $tags) {
      $service = $container->getDefinition($queue_id);

      // Extract the class, or default to SubscriptionQueue.
      $base = SubscriptionQueue::class;
      $class = $service->getClass() ?? $base;
      if (!(($class === $base) || (is_subclass_of($class, $base)))) {
        trigger_error("Ignoring service 'queue_id' (does not extend $base)", E_USER_WARNING);
        continue;
      }

      foreach ($tags as $num => $def) {
        // Subject is optional.
        $subject = $def['queue_subject'] ?? '';

        // If name is empty or duplicated, log a warning and skip it.
        $name = $def['queue_name'] ?? '';
        if (!$name) {
          trigger_error("Ignoring queue '$queue_id:$num' (no defined queue name)", E_USER_WARNING);
          continue;
        }
        if (isset($map[$name])) {
          $dupe_service = $map[$name]['service_id'] ?? 'unknown';
          trigger_error("Ignoring queue '$queue_id:$num' (already defined in service '$dupe_service')", E_USER_WARNING);
          continue;
        }
        $map[$name] = [
          'service_id' => $queue_id,
          'subject' => $subject ?: NULL,
          'class' => $class,
        ];
      }
    }

    // Set the map as an argument for the manager's definition.
    $manager->setArgument('$queueServices', $map);
  }

}
