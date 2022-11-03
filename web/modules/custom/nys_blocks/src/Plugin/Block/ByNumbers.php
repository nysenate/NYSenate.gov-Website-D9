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
  public function build() {
    $build = [];
    $build['nys_blocks_bythe_numbers']['#markup'] = 'Implement Senate By the Numbers Block.';
    $build['nys_blocks_bythe_numbers']['#theme'] = 'nys_blocks_bythe_numbers';

    return $build;
  }

}
