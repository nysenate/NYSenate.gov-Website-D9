<?php

namespace Drupal\nys_senators\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class McpRouteSubscriber for adding custom access control to routes.
 *
 * @package Drupal\nys_senators\EventSubscriber
 *
 * Removes MCP access to admin block pages to mitigate administer blocks perm.
 */
class McpRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection): void {

    // Custom access to block admin routes.
    if ($route = $collection->get('block.admin_display')) {
      $route->setRequirement('_mcp_custom_access', 'Drupal\nys_senators\Access\McpAccessCheck::access');
    }
    if ($route = $collection->get('block_content.add_page')) {
      $route->setRequirement('_mcp_custom_access', 'Drupal\nys_senators\Access\McpAccessCheck::access');
    }
    if ($route = $collection->get('entity.block_content_type.collection')) {
      $route->setRequirement('_mcp_custom_access', 'Drupal\nys_senators\Access\McpAccessCheck::access');
    }
    if ($route = $collection->get('entity.block_content.collection')) {
      $route->setRequirement('_mcp_custom_access', 'Drupal\nys_senators\Access\McpAccessCheck::access');
    }
  }

}
