<?php

namespace Drupal\nys_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Dashboard Actionbar Block.
 *
 * @Block(
 *   id = "dashboard_action_bar",
 *   admin_label = @Translation("Dashboard Actionbar"),
 * )
 */
class DashboardActionbarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $title = $this->t('My Dashboard');
    $blurb = $this->t('Browse and filter New York State Senate content that you follow.');

    return [
      '#markup' => "<h1>$title</h1><p><em>$blurb</em></p>",
    ];
  }

}
