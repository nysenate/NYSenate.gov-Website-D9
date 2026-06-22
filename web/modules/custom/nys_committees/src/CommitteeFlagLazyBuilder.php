<?php

namespace Drupal\nys_committees;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagLinkBuilderInterface;

/**
 * Lazy builder for per-user follow flag links in the committee actionbar.
 *
 * Wrapping the flag link in a lazy builder isolates the user-specific flag
 * state from the globally-cached page output, preventing cache poisoning
 * where one authenticated user's follow state is served to another user.
 */
class CommitteeFlagLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new CommitteeFlagLazyBuilder.
   *
   * @param \Drupal\flag\FlagLinkBuilderInterface $flagLinkBuilder
   *   The flag link builder service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected FlagLinkBuilderInterface $flagLinkBuilder,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderActionbarFlagLink'];
  }

  /**
   * Renders the follow/unfollow committee flag link for the actionbar.
   *
   * For anonymous users, returns a login link styled as the follow button.
   * For authenticated users, returns the per-user flag link.
   *
   * @param string $entity_type_id
   *   The entity type ID (e.g. 'taxonomy_term').
   * @param int|string $entity_id
   *   The entity ID of the committee term.
   *
   * @return array
   *   A render array for the flag link or anonymous login link.
   */
  public function renderActionbarFlagLink(string $entity_type_id, int|string $entity_id): array {
    if ($this->currentUser->isAnonymous()) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => 'follow this committee',
        '#attributes' => [
          'href' => '/user/login',
          'class' => ['icon-before__committee-follow'],
          'title' => 'follow this committee',
        ],
        '#cache' => [
          'contexts' => ['user.roles:anonymous'],
        ],
      ];
    }

    $build = $this->flagLinkBuilder->build($entity_type_id, $entity_id, 'follow_committee', 'default');
    $build['#cache']['contexts'][] = 'user';

    return $build;
  }

}
