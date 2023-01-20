<?php

namespace Drupal\nys_registration\EventSubscriber\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * NYS User Registration event subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritDoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Disable page caching on the user.register route.
    if ($route = $collection->get('user.register')) {
      $route->setOption('no_cache', 'TRUE');
    }
  }

}
