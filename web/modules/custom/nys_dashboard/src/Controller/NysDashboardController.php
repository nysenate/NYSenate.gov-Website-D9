<?php

namespace Drupal\nys_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class NysDashboardController extends ControllerBase {

  /**
   * Response for the profile page.
   */
  public function profile(): array {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Profile page'),
    ];

    return $build;
  }

}
