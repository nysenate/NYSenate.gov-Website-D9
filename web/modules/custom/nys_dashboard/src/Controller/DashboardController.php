<?php

namespace Drupal\nys_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for nys_dashboard module.
 */
class DashboardController extends ControllerBase {

  /**
   * Controller method for the entrance page.
   */
  public function overview() {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Profile page'),
    ];

    return $build;
  }

}
