<?php

namespace Drupal\nys_senators\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide senator's district information.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("senator_district")
 */
class SenatorDistrict extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // First check whether the field should be hidden if the value
    // (hide_alter_empty = TRUE) /the rewrite is empty (hide_alter_empty =
    // FALSE).
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $term = $values->_entity;
    $query = \Drupal::database()->select('taxonomy_term__field_senator', 'ttfs');
    $query->addField('ttfs', 'entity_id', 'tid');
    $query->addField('ttfdn', 'field_district_number_value', 'district_number');
    $query->leftJoin('taxonomy_term__field_district_number', 'ttfdn', 'ttfs.entity_id = ttfdn.entity_id');
    $query->isNotNull('ttfdn.entity_id');
    $query->condition('ttfs.field_senator_target_id', $term->id());
    $district_values = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($district_values) && count($district_values) === 1) {
      $district_values = reset($district_values);
      if (is_array($district_values) && array_key_exists('district_number', $district_values)) {
        $district_number = $district_values['district_number'];
        return $this->getOrdinalSuffix((int) $district_number) . ' District';
      }
    }
    return '';
  }

  /**
   * Gets ordinal suffix of the given number.
   *
   * @param int $number
   *   The district number.
   * @param int $ss
   *   Turns super script on/off.
   *
   * @return string
   *   The number with ordinal suffix.
   */
  protected function getOrdinalSuffix($number, $ss = 0) {
    // Check for 11, 12, 13.
    if ($number % 100 > 10 && $number % 100 < 14) {
      $os = 'th';
    }
    // Check if number is zero.
    elseif ($number == 0) {
      $os = '';
    }
    else {
      // Get the last digit.
      $last = substr($number, -1, 1);

      $os = match ($last) {
        "1" => 'st',
                "2" => 'nd',
                "3" => 'rd',
                default => 'th',
      };
    }

    // Add super script.
    $os = $ss == 0 ? $os : '<sup>' . $os . '</sup>';

    return $number . $os;
  }

}
