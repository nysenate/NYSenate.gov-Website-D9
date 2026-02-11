<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block for How a Bill Becomes a Law.
 *
 * @Block(
 *   id = "nys_blocks_want_to",
 *   admin_label = @Translation("I want to"),
 * )
 */
class WantTo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Use lazy builder for user-specific senator section.
    // This allows the rest of the page to be cached for anonymous users.
    $senator_section = [
      '#lazy_builder' => [
        'nys_blocks.want_to_lazy_builder:renderSenatorSection',
        [],
      ],
      '#create_placeholder' => TRUE,
    ];

    return [
      '#theme' => 'nys_blocks_want_to',
      '#senator_section' => $senator_section,
    ];
  }

}
