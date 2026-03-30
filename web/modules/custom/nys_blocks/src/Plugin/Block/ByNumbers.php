<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Block for How a Bill Becomes a Law.
 *
 * @Block(
 *   id = "nys_blocks_bythe_numbers",
 *   admin_label = @Translation("By the Numbers"),
 * )
 */
class ByNumbers extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['nys_openleg:bythe_numbers']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_year = date('Y');
    if ($current_year % 2 == 0) {
      $session_year = $current_year - 1;
      $first_year   = $session_year;
      $second_year  = substr($current_year, -2);
    }
    else {
      $session_year = $current_year;
      $first_year   = $current_year;
      $second_year  = substr($current_year + 1, -2);
    }

    return [
      '#theme' => 'nys_blocks_bythe_numbers',
      '#session_year' => $session_year,
      '#first_year' => $first_year,
      '#second_year' => $second_year,
    ];
  }

}
