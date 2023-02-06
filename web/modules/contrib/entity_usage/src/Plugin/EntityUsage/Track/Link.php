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
    if ($link->isExternal()) {
      $url = $link->getUrl()->toString();
      $entity = $this->findEntityByUrlString($url);
    }
    else {
      $url = $link->getUrl();
      $entity = $this->findEntityByRoutedUrl($url);
    }

    if (!$entity) {
      return [];
    }

    return [$entity->getEntityTypeId() . '|' . $entity->id()];
  }

}
