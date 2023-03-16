<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardSenatorsController extends ControllerBase {

  /**
   * Response for the senators page.
   */
  public function senatorManagement(): array {

    $user = $this->currentUser();
    $content['senators'] = ['#markup' => 'Senator Management'];

    return $content;
  }

  /**
   * Response for the senators page.
   */
  public function senatorPage($senator, $tab = ''): array {

    $user = $this->currentUser();
    $content['senators'] = ['#markup' => 'Senator Management Second'];

    return $content;
  }

}
