<?php

namespace Drupal\nys_list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\nys_list_formatter\Plugin\ListFormatterListInterface;

/**
 * Plugin implementation of the 'options' list formatter.
 *
 * @ListFormatter(
 *   id = "options",
 *   module = "options",
 *   field_types = {"list_boolean", "list_float", "list_integer", "list_string"}
 * )
 */
class OptionsList implements ListFormatterListInterface {

  /**
   * Implements Create List.
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, FormatterInterface $formatter, $langcode) {
    $list_items = [];

    // Only collect allowed options if there are actually items to display.
    if ($items->count()) {
      $provider = $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('value', $items->getEntity());
      // Flatten the possible options, to support opt groups.
      $options = OptGroup::flattenOptions($provider->getPossibleOptions());

      foreach ($items as $delta => $item) {
        $value = $item->value;
        // If the stored value is in the current set of allowed values, display
        // the associated label, otherwise just display the raw value.
        if (isset($options[$value])) {
          $output = $options[$value];
        }
        else {
          $output = $value;
        }
        $list_items[] = [
          '#markup' => $output,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
        ];
      }
    }

    return $list_items;
  }

  /**
   * Implements additional settings.
   */
  public function additionalSettings(array &$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
  }

}
