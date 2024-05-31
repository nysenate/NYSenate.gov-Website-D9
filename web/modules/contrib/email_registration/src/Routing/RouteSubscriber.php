<?php

declare(strict_types=1);

namespace Drupal\email_registration\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for User routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.login.http')) {
      $route->setDefault('_controller', '\Drupal\email_registration\Controller\UserHttpAuthenticationController::login');
    }
  }

}
