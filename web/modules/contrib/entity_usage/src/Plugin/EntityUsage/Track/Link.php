<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EntityUsageTrackBase;

/**
 * Tracks usage of entities related in Link fields.
 *
 * @EntityUsageTrack(
 *   id = "link",
 *   label = @Translation("Link Fields"),
 *   description = @Translation("Tracks relationships created with 'Link' fields."),
 *   field_types = {"link"},
 * )
 */
class Link extends EntityUsageTrackBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $link) {
    /** @var \Drupal\link\LinkItemInterface $link */
    // Check if the link is referencing an entity.
    $url = $link->getUrl();
    if (!$url->isRouted() || !preg_match('/^entity\./', $url->getRouteName())) {
      return [];
    }

    // Ge the target entity type and ID.
    $route_parameters = $url->getRouteParameters();
    $target_type = array_keys($route_parameters)[0];
    $target_id = $route_parameters[$target_type];

    // Only return a valid result if the target entity exists.
    try {
      if (!$this->entityTypeManager->getStorage($target_type)->load($target_id)) {
        return [];
      }
    }
    catch (\Exception $exception) {
      return [];
    }

    return [$target_type . '|' . $target_id];
  }

}
