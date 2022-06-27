<?php

namespace Drupal\entityqueue\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entityqueue routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Try to get the route from the current collection.
      $link_template = $entity_type->getLinkTemplate('canonical');
      if (strpos($link_template, '/') !== FALSE) {
        $base_path = '/' . $link_template;
      }
      else {
        if (!$entity_route = $collection->get("entity.$entity_type_id.canonical")) {
          continue;
        }
        $base_path = $entity_route->getPath();
      }

      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;
      $route_name = "entity.$entity_type_id.edit_form";
      if ($edit_route = $collection->get($route_name)) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $path = $base_path . '/entityqueue';

      $route = new Route(
        $path,
        [
          '_controller' => '\Drupal\entityqueue\Controller\EntityQueueUIController::subqueueListForEntity',
          'entity_type_id' => $entity_type_id,
          '_title' => 'Entityqueues',
        ],
        [
          '_permission' => 'administer entityqueue+manipulate entityqueues+manipulate all entityqueues',
          '_custom_access' => 'Drupal\entityqueue\Controller\EntityQueueUIController::access',
        ],
        [
          'parameters' => [
            $entity_type_id => [
              'type' => 'entity:' . $entity_type_id,
            ],
          ],
          '_admin_route' => $is_admin,
        ]
      );
      $route_name = "entity.$entity_type_id.entityqueue";
      $collection->add($route_name, $route);

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Should run after AdminRouteSubscriber so the routes can inherit admin
    // status of the edit routes on entities. Therefore priority -210.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];

    return $events;
  }

}
