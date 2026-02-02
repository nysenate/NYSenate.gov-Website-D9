<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
  public function getCacheMaxAge(): int {
    return 86400;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $facts = [];
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

    // Set static status for the facts.
    $facts[0]['status'] = 'signed';
    $facts[1]['status'] = 'waiting';
    $facts[2]['status'] = 'vetoed';

    $bill_status = '';
    foreach ($facts as $key => $fact) {
      switch ($fact['status']) {
        case 'signed':
          $bill_status = 'SIGNED_BY_GOV';
          break;

        case 'waiting':
          $bill_status = 'DELIVERED_TO_GOV';
          break;

        case 'vetoed':
          $bill_status = 'VETOED';
          break;

        default:
          break;
      }
    }

    return [
      '#theme' => 'nys_blocks_bythe_numbers',
      '#session_year' => $session_year,
      '#first_year' => $first_year,
      '#second_year' => $second_year,
    ];
  }

}
