<?php

namespace Drupal\scheduler\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Sets access for specific scheduler views routes.
 */
class SchedulerRouteAccess {

  /**
   * Provides custom access checks for the scheduled views on the user page.
   *
   * A user is given access if either of the following conditions are met:
   * - they are viewing their own page and they have the permission to schedule
   * content or view scheduled content of the required type.
   * - they are viewing another user's page and they have permission to view
   * user profiles and view scheduled content, and the user they are viewing has
   * permission to schedule content or view scheduled content.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    $user_being_viewed = $route_match->getParameter('user');
    $viewing_own_page = $user_being_viewed == $account->id();

    // getUserPageViewRoutes() returns an array of user page view routes, keyed
    // on the entity id. Use this to get the entity id.
    $scheduler_manager = \Drupal::service('scheduler.manager');
    $entityTypeId = array_search($route_match->getRouteName(), $scheduler_manager->getUserPageViewRoutes());
    $viewing_permission_name = $scheduler_manager->permissionName($entityTypeId, 'view');
    $scheduling_permission_name = $scheduler_manager->permissionName($entityTypeId, 'schedule');

    if ($viewing_own_page && ($account->hasPermission($viewing_permission_name) || $account->hasPermission($scheduling_permission_name))) {
      return AccessResult::allowed();
    }
    if (!$viewing_own_page && $account->hasPermission($viewing_permission_name) && $account->hasPermission('access user profiles')) {
      $other_user = User::load($user_being_viewed);
      if ($other_user && ($other_user->hasPermission($viewing_permission_name) || $other_user->hasPermission($scheduling_permission_name))) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
