<?php

namespace Drupal\nys_blocks;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\nys_senators\Service\Microsites;
use Drupal\taxonomy\TermInterface;

/**
 * Lazy builder for user-specific content in the "I Want To" block.
 */
class WantToLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new WantToLazyBuilder.
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
    return ['renderSenatorSection'];
  }

  /**
   * Renders the senator section with user-specific content.
   *
   * @return array
   *   A render array for the senator section.
   */
  public function renderSenatorSection(): array {
    $headshot = NULL;
    $senator_link = NULL;
    $logged_in = $this->currentUser->isAuthenticated();

    if ($logged_in) {
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      if ($user) {
        $senator = $user->get('field_district')->entity?->field_senator->entity ?? NULL;
        $senator_link = ($senator instanceof TermInterface)
          ? $this->microsites->getMicrosite($senator)
          : NULL;
        $image = $user->get('field_district')->entity?->field_senator
          ->entity?->field_member_headshot->entity ?? NULL;
        $headshot = $image
          ? $this->entityTypeManager
            ->getViewBuilder('media')
            ->view($image, 'thumbnail')
          : NULL;
      }
    }

    $register = Url::fromRoute('user.register')->toString();

    return [
      '#theme' => 'nys_blocks_want_to_senator_section',
      '#headshot' => $headshot,
      '#senator_link' => $senator_link,
      '#register' => $register,
      '#logged_in' => $logged_in,
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 1800,
      ],
    ];
  }

}
