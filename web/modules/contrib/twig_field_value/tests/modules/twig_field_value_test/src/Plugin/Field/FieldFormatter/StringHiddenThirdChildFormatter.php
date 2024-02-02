<?php

namespace Drupal\twig_field_value_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * The string formatter.
 *
 * @FieldFormatter(
 *   id = "string_hidden_third_child",
 *   label = @Translation("Without third child"),
 *   description = @Translation("Denies access to every third field item. Only displays every first and second item as plain text."),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class StringHiddenThirdChildFormatter extends StringFormatter {

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
