<?php

namespace Drupal\nys_dashboard;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;

/**
 * Lazy builder for user-specific header menu content.
 *
 * This ensures that the user menu in the header is cached separately
 * per user, preventing cache poisoning where one user sees another
 * user's name and personalized links.
 */
class UserMenuLazyBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderUserMenu', 'renderUserMenuMobile'];
  }

  /**
   * Renders the user menu with user-specific content.
   *
   * This method is called after the main page cache is served, allowing
   * per-user content to be injected into otherwise cached pages.
   *
   * @return array
   *   A render array for the user menu section.
   */
  public static function renderUserMenu(): array {
    $current_user = \Drupal::currentUser();
    $is_logged = $current_user->isAuthenticated();

    // Default values for anonymous users.
    $user_first_name = 'Guest';
    $dashboard_link = '/dashboard';
    $manage_dashboard_link = '/dashboard/manage';
    $edit_account_link = '/dashboard/edit';
    $has_senator = FALSE;
    $senator_microsite_link = NULL;
    $senator_image = NULL;

    if ($is_logged) {
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($current_user->id());

      if ($user) {
        // Get user's first name.
        $user_first_name = $user->field_first_name?->value ?? 'Guest';

        // Get user's senator information.
        $senator = $user->get('field_district')->entity->field_senator->entity ?? NULL;
        $headshot = $senator->field_member_headshot->entity ?? NULL;

        if (($senator instanceof TermInterface) && $headshot) {
          $has_senator = TRUE;
          $senator_microsite_link = \Drupal::service('nys_senators.microsites')->getMicrosite($senator);
          $senator_image = \Drupal::entityTypeManager()
            ->getViewBuilder('media')
            ->view($headshot, 'thumbnail');
        }
      }
    }

    // Return a render array that will be embedded in the header.
    return [
      '#theme' => 'nys_dashboard_user_menu',
      '#is_logged' => $is_logged,
      '#user_first_name' => $user_first_name,
      '#dashboard_link' => $dashboard_link,
      '#manage_dashboard_link' => $manage_dashboard_link,
      '#edit_account_link' => $edit_account_link,
      '#has_senator' => $has_senator,
      '#senator_microsite_link' => $senator_microsite_link,
      '#senator_image' => $senator_image,
      // Cache this per user and by authentication status.
      '#cache' => [
        'contexts' => ['user'],
        'tags' => $is_logged && isset($user) ? $user->getCacheTags() : [],
      ],
    ];
  }

  /**
   * Renders the mobile user menu with user-specific content.
   *
   * This is a separate method for the mobile menu to keep the render
   * arrays independent.
   *
   * @return array
   *   A render array for the mobile user menu section.
   */
  public static function renderUserMenuMobile(): array {
    $current_user = \Drupal::currentUser();
    $is_logged = $current_user->isAuthenticated();

    // Default values for anonymous users.
    $user_first_name = 'Guest';
    $dashboard_link = '/dashboard';
    $manage_dashboard_link = '/dashboard/manage';
    $edit_account_link = '/dashboard/edit';
    $senator_microsite_link = NULL;

    if ($is_logged) {
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($current_user->id());

      if ($user) {
        // Get user's first name.
        $user_first_name = $user->field_first_name?->value ?? 'Guest';

        // Get user's senator microsite link.
        $senator = $user->get('field_district')->entity->field_senator->entity ?? NULL;
        if ($senator instanceof TermInterface) {
          $senator_microsite_link = \Drupal::service('nys_senators.microsites')->getMicrosite($senator);
        }
      }
    }

    // Return a render array for mobile menu.
    return [
      '#theme' => 'nys_dashboard_user_menu_mobile',
      '#is_logged' => $is_logged,
      '#user_first_name' => $user_first_name,
      '#dashboard_link' => $dashboard_link,
      '#manage_dashboard_link' => $manage_dashboard_link,
      '#edit_account_link' => $edit_account_link,
      '#senator_microsite_link' => $senator_microsite_link,
      // Cache this per user and by authentication status.
      '#cache' => [
        'contexts' => ['user'],
        'tags' => $is_logged && isset($user) ? $user->getCacheTags() : [],
      ],
    ];
  }

}
