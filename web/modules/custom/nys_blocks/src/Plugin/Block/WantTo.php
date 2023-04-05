<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Block for How a Bill Becomes a Law.
 *
 * @Block(
 *   id = "nys_blocks_want_to",
 *   admin_label = @Translation("I want to"),
 * )
 */
class WantTo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 1800;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $logged_in = \Drupal::currentUser()->isAuthenticated();
    if ($logged_in) {
      $current_user = \Drupal::currentUser();
      $user = User::load($current_user->id());
      $senator = $user->get('field_district')->entity->field_senator->entity;
      $senator_link = \Drupal::service('nys_senators.microsites')->getMicrosite($senator);
      $headshot_id = $user->field_district->entity->field_senator
        ->entity->field_member_headshot->target_id;
      $headshot = \Drupal::entityTypeManager()->getStorage('media')
        ->load($headshot_id);
      $headshot = \Drupal::entityTypeManager()
        ->getViewBuilder('media')
        ->view($headshot, 'thumbnail');
    }
    $register = Url::fromRoute('user.register')->toString();

    return [
      '#theme' => 'nys_blocks_want_to',
      '#headshot' => $headshot ?? NULL,
      '#senator_link' => $senator_link ?? NULL,
      '#register' => $register,
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

}
