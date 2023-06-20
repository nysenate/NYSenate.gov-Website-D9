<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\nys_users\UsersHelper;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardSenatorsController extends ControllerBase {

  /**
   * Response for the "Senator Management" tab on the user dashboard.
   *
   * Displays links to the management page of each senator to which the current
   * user has LC/MCP access. The list is sorted by "<last_name> <first_name>".
   */
  public function senatorManagement(): array {

    // Get the User entity for the current user.
    $user = UsersHelper::resolveUser($this->currentUser());

    // Collect the senator references based on user permissions.
    $senators = [];
    $to_add = [
      'isMcp' => 'field_senator_multiref',
      'isLc' => 'field_senator_inbox_access',
    ];
    foreach ($to_add as $perm => $field) {
      if (UsersHelper::{$perm}($user)) {
        $senators = array_merge(
              $senators,
              $user->{$field}->referencedEntities() ?? []
          );
      }
    }
    SenatorsHelper::sortByName($senators);

    $content = [];
    $viewer = $this->entityTypeManager()->getViewBuilder('taxonomy_term');
    /**
* @var \Drupal\taxonomy\Entity\Term $senator
*/
    foreach ($senators as $senator) {
      $content['senator_' . $senator->id()] = [
        '#attributes' => ['class' => ['senator_management_link']],
        '#type' => 'container',
        'senator' => $viewer->view($senator, 'dashboard'),
      ];
    }

    return $content;
  }

}
