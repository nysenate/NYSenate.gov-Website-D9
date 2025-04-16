<?php

namespace Drupal\nys_senator_dashboard\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides helper methods for the nys_senator_dashboard module.
 */
class SenatorDashboardHelper {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs the ManagedSenatorsHandler service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RouteMatchInterface $route_match,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $route_match;
  }

  /**
   * Gets the entity whose ID is passed into a contextual filter.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object, or NULL on failure.
   */
  public function getContextualEntity() {
    $entity_id = $this->routeMatch->getParameter('arg_0');
    if (!$entity_id) {
      return NULL;
    }
    $target_entity_types = ['node', 'taxonomy_term'];
    foreach ($target_entity_types as $entity_type) {
      try {
        $entity = $this->entityTypeManager
          ->getStorage($entity_type)
          ->load($entity_id);
      }
      catch (\Exception) {
        return NULL;
      }
      if (!empty($entity)) {
        break;
      }
    }
    return $entity ?? NULL;
  }

}
