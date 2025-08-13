<?php

namespace Drupal\nys_senator_dashboard\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modifies existing routes for the nys_senator_dashboard module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $target_view_displays = ['bill_responses', 'issue_followers', 'petition_signees', 'questionnaires'];
    foreach ($target_view_displays as $view_display) {
      if ($route = $collection->get("view.senator_dashboard_constituents.$view_display")) {
        $route->setDefault('_title_callback', '\Drupal\nys_senator_dashboard\Controller\SenatorDashboardController::contextualDetailPageTitle');
      }
    }
  }

}
