<?php

namespace Drupal\entityqueue\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides HTML routes for entity subqueues.
 */
class EntitySubqueueRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    return parent::getEditFormRoute($entity_type);
  }

}
