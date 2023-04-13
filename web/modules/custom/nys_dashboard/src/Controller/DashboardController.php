<?php

namespace Drupal\nys_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_users\UsersHelper;

/**
 * Controller class for nys_dashboard module.
 */
class DashboardController extends ControllerBase {

  /**
   * Controller method for the entrance page.
   */
  public function overview() {
    $user = UsersHelper::resolveUser($this->currentUser());
    $build['content'] = \Drupal::service('entity_type.manager')
      ->getViewBuilder('user')->view($user, 'dashboard_profile');

    return $build;
  }

}
