<?php

namespace Drupal\nys_dashboard\EventSubscriber\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * NYS User Dashboard event subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritDoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Generally, viewing a user's profile through the canonical link is
    // disallowed - the NYS user dashboard is used instead.  It might be
    // a better idea to redirect this route instead of disabling it.
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
