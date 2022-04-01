<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EntityUsageTrackBase;

/**
 * Tracks usage of entities related in entity_reference fields.
 *
 * @EntityUsageTrack(
 *   id = "entity_reference",
 *   label = @Translation("Entity Reference"),
 *   description = @Translation("Tracks relationships created with 'Entity Reference' fields."),
 *   field_types = {"entity_reference", "entity_reference_revisions"},
 * )
 */
class EntityReference extends EntityUsageTrackBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    $item_value = $item->getValue();
    if (empty($item_value['target_id'])) {
      return [];
    }
    $target_type = $item->getFieldDefinition()->getSetting('target_type');

    // Only return a valid result if the target entity exists.
    if (!$this->entityTypeManager->getStorage($target_type)->load($item_value['target_id'])) {
      return [];
    }

    return [$target_type . '|' . $item_value['target_id']];
  }

}
