<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EntityUsageTrackBase;

/**
 * Tracks usage of entities related in dynamic_entity_reference fields.
 *
 * @EntityUsageTrack(
 *   id = "dynamic_entity_reference",
 *   label = @Translation("Dynamic Entity Reference"),
 *   description = @Translation("Tracks relationships created with 'Dynamic Entity Reference' fields."),
 *   field_types = {"dynamic_entity_reference"},
 * )
 */
class DynamicEntityReference extends EntityUsageTrackBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    /** @var \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem$item */
    $item_value = $item->getValue();
    if (empty($item_value['target_id']) || empty($item_value['target_type'])) {
      return [];
    }
    // Only return a valid result if the target entity exists.
    if (!$this->entityTypeManager->getStorage($item_value['target_type'])->load($item_value['target_id'])) {
      return [];
    }

    return [$item_value['target_type'] . '|' . $item_value['target_id']];
  }

}
