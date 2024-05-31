<?php

namespace Drupal\twig_field_value_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * The string formatter.
 *
 * @FieldFormatter(
 *   id = "string_hidden_field",
 *   label = @Translation("Hidden field"),
 *   description = @Translation("Denies access to this field."),
 *   field_types = {
 *     "string",
 *   },
 * )
 */
class StringHiddenFieldFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#access'] = FALSE;
    return $elements;
  }

}
