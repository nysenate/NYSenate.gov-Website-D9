<?php

namespace Drupal\twig_field_value_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Entity reference formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_hidden_field",
 *   label = @Translation("Hidden field"),
 *   description = @Translation("Denies access to this field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferencesHiddenFieldFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#access'] = FALSE;
    return $elements;
  }

}
