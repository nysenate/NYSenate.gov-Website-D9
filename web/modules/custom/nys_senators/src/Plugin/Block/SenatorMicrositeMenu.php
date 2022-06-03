<?php

namespace Drupal\nys_senators\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block to generate the menu for all of the Senator Microsite Pages.
 *
 * @Block(
 *   id = "nys_senators_microsite_menu",
 *   admin_label = @Translation("Senator Microsite Menu"),
 *   category = @Translation("NYS Senators")
 * )
 */
class SenatorMicrositeMenu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var Node $node */
    $node = $this->getContextValue('node');
    if (!empty($node) && $node->getType() == 'microsite_page') {
      // Query to get all the "Microsite Pages" with the same Senator reference
      // Get the Senator taxonomy reference from there populate links.
      foreach ($nodes as $node) {
        // Get the url alias for each and populate links for menu block.
        $links[] = $node->toUrl()->toString();
      }
      // Extract the Senator term from the node or taxonomy page to set the
      // district populate the district link from the taxonomy term's url alias.
      // Set up links so they can render the menu block template.
    }
  }

}
