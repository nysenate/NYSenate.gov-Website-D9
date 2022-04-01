<?php

namespace Drupal\node_revision_delete\Utility;

/**
 * Provides module internal helper methods.
 *
 * @ingroup utility
 */
class Time {

  /**
   * Return a mapping of the old word values to numeric equivalents.
   *
   * @param string $word
   *   The old word to map.
   *
   * @return array|string
   *   The numeric value when a word is provided or the whole map.
   */
  public static function convertWordToTime($word = NULL) {
    $word_map = [
      'never'           => '-1',
      'every_time'      => '0',
      'every_hour'      => '3600',
      'everyday'        => '86400',
      'every_week'      => '604800',
      'every_10_days'   => '864000',
      'every_15_days'   => '1296000',
      'every_month'     => '2592000',
      'every_3_months'  => '7776000',
      'every_6_months'  => '15552000',
      'every_year'      => '31536000',
      'every_2_years'   => '63072000',
    ];

    return empty($word) ? $word_map : $word_map[$word];
  }

}
