<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class NysDashboardSenatorsController extends ControllerBase {

  /**
   * Response for the senators page.
   */
  public function senatorManagement(): array {

    $content['senators'] = ['#markup' => 'Senator Management'];

    return $content;
  }

  /**
   * Response for the senators page.
   */
  public function senatorManagementSecond(): array {

    $content['senators'] = ['#markup' => 'Senator Management Second'];

    return $content;
  }

}
