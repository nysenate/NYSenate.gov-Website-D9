<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block for Contact Us.
 *
 * @Block(
 *   id = "nys_blocks_contact_us",
 *   admin_label = @Translation("Contact Us"),
 * )
 */
class ContactUs extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['nys_blocks_contact_us']['#markup'] = 'Implement Contact Us Block.';
    $build['nys_blocks_contact_us']['#theme'] = 'nys_blocks_contact_us';

    return $build;
  }

}
