<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\nys_subscriptions\DependencyInjection\Compiler\SubscriptionQueueCollector;

/**
 * Adds the queue collector to support lazy loading.
 */
class NysSubscriptionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new SubscriptionQueueCollector());
  }

}
