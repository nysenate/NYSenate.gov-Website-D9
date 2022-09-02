<?php

namespace Drupal\nys_list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\nys_list_formatter\Plugin\ListFormatterListInterface;

/**
 * Default list implementation plugin.
 *
 * @ListFormatter(
 *   id = "default",
 *   module = "list_formatter",
 *   field_types = {}
 * )
 */
class DefaultList implements ListFormatterListInterface {

  /**
   * Implements ListFormatterListInterface::createList().
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, FormatterInterface $formatter, $langcode) {
    $list_items = [];

    // Use our helper function to get the value key dynamically.
    $value_key = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

    foreach ($items as $delta => $item) {
      $list_items[$delta] = [
        '#markup' => $item->{$value_key},
        '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
      ];
    }

    return $list_items;
  }

  /**
   * Implements additional settings.
   */
  public function additionalSettings(array &$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
  }

}
