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

  /**
   * Calculates the ordinal suffix for a number.
   *
   * E.g., to make "2" look like "2nd".  This is here because it is only used
   * with entity fields for now.  Reorganize this to somewhere reasonable if
   * usage is expanded, or if more utility functions are added.
   */
  protected function ordinalSuffix(int $number): string {
    // Check if number is zero.
    if ($number === 0) {
      $os = '';
    }
    // Check for 11, 12, 13.
    elseif (in_array($number % 100, [11, 12, 13])) {
      $os = 'th';
    }
    else {
      $os = match ($number % 10) {
        1 => 'st',
        2 => 'nd',
        3 => 'rd',
        default => 'th',
      };
    }

    return $os;
  }

}
