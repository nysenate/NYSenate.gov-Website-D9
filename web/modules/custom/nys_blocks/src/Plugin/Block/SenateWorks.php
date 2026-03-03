<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block for How a Bill Becomes a Law.
 *
 * @Block(
 *   id = "nys_blocks_senate_works",
 *   admin_label = @Translation("How a Bill Becomes a Law"),
 * )
 */
class SenateWorks extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['nys_blocks_senate_works']['#markup'] = 'Implement Senate Works Block.';
    $build['nys_blocks_senate_works']['#theme'] = 'nys_blocks_senate_works';

    return $build;
  }

}
