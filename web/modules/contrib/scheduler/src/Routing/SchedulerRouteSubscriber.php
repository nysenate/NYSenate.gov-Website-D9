<?php

namespace Drupal\scheduler\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Scheduler route subscriber to add custom access for user views.
 */
class SchedulerRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $user_page_routes = \Drupal::service('scheduler.manager')->getUserPageViewRoutes();
    foreach ($user_page_routes as $user_route) {
      if ($route = $collection->get($user_route)) {
        $requirements = $route->getRequirements();
        $requirements['_custom_access'] = '\Drupal\scheduler\Access\SchedulerRouteAccess::access';
        $route->setRequirements($requirements);
      }
    }
  }

}
