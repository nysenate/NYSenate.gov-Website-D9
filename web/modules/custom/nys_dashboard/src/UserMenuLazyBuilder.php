<?php

namespace Drupal\nys_dashboard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\nys_senators\Service\Microsites;
use Drupal\taxonomy\TermInterface;

/**
 * Lazy builder for user-specific header menu content.
 *
 * This ensures that the user menu in the header is cached separately
 * per user, preventing cache poisoning where one user sees another
 * user's name and personalized links.
 */
class UserMenuLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new UserMenuLazyBuilder.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\nys_senators\Service\Microsites $microsites
   *   The microsites service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected Microsites $microsites,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

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
  public function renderUserMenu(): array {
    $data = $this->loadUserData();

    $has_senator = FALSE;
    $senator_image = NULL;
    if ($data['senator'] instanceof TermInterface) {
      $headshot = $data['senator']->field_member_headshot->entity ?? NULL;
      if ($headshot) {
        $has_senator = TRUE;
        $senator_image = $this->entityTypeManager
          ->getViewBuilder('media')
          ->view($headshot, 'thumbnail');
      }
    }

    return [
      '#theme' => 'nys_dashboard_user_menu',
      '#is_logged' => $data['is_logged'],
      '#user_first_name' => $data['user_first_name'],
      '#dashboard_link' => $data['dashboard_link'],
      '#manage_dashboard_link' => $data['manage_dashboard_link'],
      '#edit_account_link' => $data['edit_account_link'],
      '#has_senator' => $has_senator,
      '#senator_microsite_link' => $data['senator_microsite_link'],
      '#senator_image' => $senator_image,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => $data['user'] ? $data['user']->getCacheTags() : [],
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
  public function renderUserMenuMobile(): array {
    $data = $this->loadUserData();

    return [
      '#theme' => 'nys_dashboard_user_menu_mobile',
      '#is_logged' => $data['is_logged'],
      '#user_first_name' => $data['user_first_name'],
      '#dashboard_link' => $data['dashboard_link'],
      '#manage_dashboard_link' => $data['manage_dashboard_link'],
      '#edit_account_link' => $data['edit_account_link'],
      '#senator_microsite_link' => $data['senator_microsite_link'],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => $data['user'] ? $data['user']->getCacheTags() : [],
      ],
    ];
  }

  /**
   * Loads user-specific data shared by both desktop and mobile menu renders.
   *
   * Centralises the user/senator resolution so renderUserMenu() and
   * renderUserMenuMobile() stay in sync without duplicating logic.
   *
   * @return array
   *   Associative array with keys: is_logged, user_first_name, dashboard_link,
   *   manage_dashboard_link, edit_account_link, senator_microsite_link,
   *   senator (TermInterface|null), user (UserInterface|null).
   */
  private function loadUserData(): array {
    $is_logged = $this->currentUser->isAuthenticated();
    $data = [
      'is_logged' => $is_logged,
      'user_first_name' => 'Guest',
      'dashboard_link' => '/dashboard',
      'manage_dashboard_link' => '/dashboard/manage',
      'edit_account_link' => '/dashboard/edit',
      'senator_microsite_link' => NULL,
      'senator' => NULL,
      'user' => NULL,
    ];

    if (!$is_logged) {
      return $data;
    }

    /** @var \Drupal\user\UserInterface|null $user */
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if (!$user) {
      return $data;
    }

    $data['user'] = $user;
    $data['user_first_name'] = $user->get('field_first_name')->value ?? 'Guest';

    $senator = $user->get('field_district')->entity?->field_senator->entity ?? NULL;
    if ($senator instanceof TermInterface) {
      $data['senator'] = $senator;
      $data['senator_microsite_link'] = $this->microsites->getMicrosite($senator);
    }

    return $data;
  }

}
