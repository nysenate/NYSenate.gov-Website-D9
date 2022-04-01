<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EmbedTrackInterface;
use Drupal\entity_usage\EntityUsageTrackBase;

/**
 * Base class for plugins tracking usage in entities embedded in WYSIWYG fields.
 */
abstract class TextFieldEmbedBase extends EntityUsageTrackBase implements EmbedTrackInterface {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    $item_value = $item->getValue();
    if (empty($item_value['value'])) {
      return [];
    }
    $text = $item_value['value'];
    if ($item->getFieldDefinition()->getType() === 'text_with_summary') {
      $text .= $item_value['summary'];
    }
    $entities_in_text = $this->parseEntitiesFromText($text);
    $valid_entities = [];
    foreach ($entities_in_text as $uuid => $entity_type) {
      // Check if the target entity exists since text fields are not
      // automatically updated when an entity is removed.
      if ($target_entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid)) {
        $valid_entities[] = $target_entity->getEntityTypeId() . "|" . $target_entity->id();
      }
    }
    return array_unique($valid_entities);
  }

}
