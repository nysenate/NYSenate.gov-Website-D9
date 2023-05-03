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
    $build = [
      '#theme' => 'nys_dashboard_overview',
      '#attached' => [
        'library' => [
          'nysenate_theme/dashboard-profile',
        ],
      ],
    ];
    $user = UsersHelper::resolveUser($this->currentUser());
    if ($user) {
      $build['#user'] = \Drupal::service('entity_type.manager')
        ->getViewBuilder('user')->view($user, 'dashboard_profile');

      // Get User's District Senator.
      $senator = UsersHelper::getSenator($user);
      if ($senator) {
        $build['#my_senator'] = \Drupal::service('entity_type.manager')
          ->getViewBuilder('taxonomy_term')->view($senator, 'senator_profile');
        $build['#message_form'] = \Drupal::service('form_builder')
          ->getForm('Drupal\nys_messaging\Form\SenatorMessageForm', $user->id(), 'profile');
      }
    }

    return $build;
  }

}
