<?php

namespace Drupal\nys_list_formatter\Plugin;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;

/**
 * Defines an interface for List Formatter plugins.
 */
interface ListFormatterListInterface {

  /**
   * Creates a list from field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Interface definition for List Items.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Assign base field definitions.
   * @param \Drupal\Core\Field\FormatterInterface $formatter
   *   Interface definition for field formatter plugins.
   * @param string $langcode
   *   The ID of the language code.
   *
   * @return array
   *   The settings of this plugin.
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, FormatterInterface $formatter, $langcode);

  /**
   * Additional Settings description.
   *
   * @param array $elements
   *   Containing the structure of the form.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Assign base field definitions.
   * @param \Drupal\Core\Field\FormatterInterface $formatter
   *   Interface definition for field formatter plugins.
   */
  public function additionalSettings(array &$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter);

}
