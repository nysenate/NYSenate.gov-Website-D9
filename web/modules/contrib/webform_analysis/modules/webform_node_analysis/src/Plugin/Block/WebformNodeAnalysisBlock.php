<?php

namespace Drupal\webform_node_analysis\Plugin\Block;

use Drupal\webform_analysis\Plugin\Block\WebformAnalysisBlock;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "webform_node_analysis_block",
 *   admin_label = @Translation("Webform Node Analysis"),
 *   category = @Translation("Webform")
 * )
 */
class WebformNodeAnalysisBlock extends WebformAnalysisBlock {

  /**
   * {@inheritdoc}
   */
  public static function elementEntityTypeId() {
    return 'node';
  }

}
