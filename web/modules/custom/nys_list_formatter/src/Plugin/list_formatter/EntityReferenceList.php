<?php

namespace Drupal\nys_list_formatter\Plugin\list_formatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\nys_list_formatter\Plugin\ListFormatterListInterface;

/**
 * Plugin implementation of the 'entity_reference' list formatter.
 *
 * @ListFormatter(
 *   id = "entity_reference",
 *   module = "entity_reference",
 *   field_types = {"entity_reference"},
 *   settings = {
 *     "entity_reference_link" = "1"
 *   }
 * )
 */
class EntityReferenceList implements ListFormatterListInterface {

  /**
   * Implements ListFormatterListInterface::createList().
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, FormatterInterface $formatter, $langcode) {
    // Load the target type for the field instance.
    $target_type = $field_definition->getSetting('target_type');
    $contrib_settings = $formatter->getSetting('list_formatter_contrib');
    $list_items = $target_ids = $target_entities = [];

    // Get an array of entity ids.
    foreach ($items as $delta => $item) {
      $target_ids[] = $item->target_id;
    }

    // Load them all.
    if ($target_ids) {
      $target_entities = \Drupal::entityTypeManager()->getStorage($target_type)->loadMultiple($target_ids);
    }

    // Create a list item for each entity.
    foreach ($target_entities as $id => $entity) {
      // Only add entities to the list that the user will have access to.
      if ($entity->access('view')) {
        $label = $entity->label();
        $reference = $contrib_settings['entity_reference_link'] ?? '';
        if ($reference) {
          $url = $entity->toUrl();
          $target_type_class = Html::getClass($target_type);
          $classes = [
            $target_type_class, $target_type_class . '-' . $id, 'entity-reference',
          ];

          $list_items[$id] = [
            '#type' => 'link',
            '#title' => $label,
            '#url' => $url,
            '#options' => [
              'attributes' => [
                'class' => $classes,
              ],
            ],
          ];
        }
        else {
          $list_items[$id] = [
            '#markup' => $label,
            '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
          ];
        }
      }
    }

    return $list_items;
  }

  /**
   * Implements additional settings.
   */
  public function additionalSettings(array &$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
    if ($field_definition->getType() == 'entity_reference') {
      $settings = $formatter->getSetting('list_formatter_contrib');
      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
      $entity_type = \Drupal::entityTypeManager()->getDefinition($target_type);
      $elements['list_formatter_contrib']['entity_reference_link'] = [
        '#type' => 'checkbox',
        '#title' => t('Link list items to their @entity_type entity.', ['@entity_type' => strtolower($entity_type->getLabel())]),
        '#default_value' => $settings['entity_reference_link'] ?? '',
      ];
    }
  }

}
