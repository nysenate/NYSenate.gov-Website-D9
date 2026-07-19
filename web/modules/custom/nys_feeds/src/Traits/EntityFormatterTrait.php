<?php

namespace Drupal\nys_feeds\Traits;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Methods to format entities and entity references.
 */
trait EntityFormatterTrait {

  /**
   * Retrieve the internal URL for a content entity.
   */
  protected function getUrl(?ContentEntityInterface $entity = NULL, $rel = 'canonical', $options = []): string {
    $url = '';
    if ($entity) {
      try {
        $url = $entity->toUrl($rel, $options)->toString();
      }
      catch (\Throwable) {
        $url = 'Error rendering URL';
      }
    }
    return $url;
  }

  /**
   * Gets an array of labels from an entity reference field.
   */
  protected function getReferencedLabels(EntityReferenceFieldItemList $field): array {
    // Compile issues.
    return array_filter(array_map(
      fn($entity) => $entity->label(),
      $field->referencedEntities() ?? []
    ));
  }

}
