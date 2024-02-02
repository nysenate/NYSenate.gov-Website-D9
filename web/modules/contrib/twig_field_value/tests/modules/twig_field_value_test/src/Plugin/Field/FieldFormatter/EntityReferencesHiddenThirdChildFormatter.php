<?php

namespace Drupal\twig_field_value_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Entity reference formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_hidden_third_child",
 *   label = @Translation("Without third child"),
 *   description = @Translation("Denies access to every third referenced entity. Only displays the label of every first and second item."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferencesHiddenThirdChildFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach (array_keys($elements) as $delta) {
      if ($this->isThirdChild($delta)) {
        $elements[$delta]['#access'] = FALSE;
      }
    }

    return $elements;
  }

  /**
   * Determine if this is the third item.
   *
   * @param int $key
   *   Key of indexed array.
   *
   * @return bool
   *   True for every third child.
   */
  private function isThirdChild($key) {
    return $key % 3 == 2;
  }

}
