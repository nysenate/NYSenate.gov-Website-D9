<?php

namespace Drupal\nys_senator_dashboard\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Field formatter that returns a 10-year "age group" for a given datetime.
 */
#[FieldFormatter(
  id: 'nys_senator_dashboard_datetime_age_group',
  label: new TranslatableMarkup('NYS Senator Dashboard: Age Group'),
  field_types: ['datetime']
)]
class AgeGroupFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $key => $item) {
      if ($item->value) {
        $timestamp = strtotime($item->value);
        $years_since = date('Y') - date('Y', $timestamp);
        if ($years_since < 0) {
          continue;
        }
        $lower_bound = floor($years_since / 10) * 10;
        $upper_bound = $lower_bound + 9;
        $range = "{$lower_bound}-{$upper_bound}";
        $elements[$key] = ['#markup' => $range];
      }
    }
    return $elements;
  }

}
