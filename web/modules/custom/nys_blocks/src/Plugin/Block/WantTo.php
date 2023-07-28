<?php

namespace Drupal\nys_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

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
  public function build(): array {

    if (\Drupal::currentUser()->isAuthenticated()) {
      /** @var \Drupal\user\Entity\User $user */
      $user = \Drupal::currentUser()->getAccount();
      $senator = $user->get('field_district')->entity->field_senator->entity ?? NULL;
      $senator_link = ($senator instanceof TermInterface)
        ? \Drupal::service('nys_senators.microsites')->getMicrosite($senator)
        : NULL;
      $image = $user->get('field_district')->entity->field_senator
        ->entity->field_member_headshot->entity;
      $headshot = \Drupal::entityTypeManager()
        ->getViewBuilder('media')
        ->view($image, 'thumbnail');
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
