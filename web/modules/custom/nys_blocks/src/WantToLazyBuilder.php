<?php

namespace Drupal\nys_blocks;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;

/**
 * Lazy builder for user-specific content in the "I Want To" block.
 */
class WantToLazyBuilder implements TrustedCallbackInterface {

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
  public static function renderSenatorSection(): array {
    $headshot = NULL;
    $senator_link = NULL;
    $logged_in = \Drupal::currentUser()->isAuthenticated();

    if ($logged_in) {
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load(\Drupal::currentUser()->id());
      if ($user) {
        $senator = $user->get('field_district')->entity->field_senator->entity ?? NULL;
        $senator_link = ($senator instanceof TermInterface)
          ? \Drupal::service('nys_senators.microsites')->getMicrosite($senator)
          : NULL;
        $image = $user->get('field_district')->entity->field_senator
          ->entity->field_member_headshot->entity ?? NULL;
        $headshot = $image
          ? \Drupal::entityTypeManager()
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
