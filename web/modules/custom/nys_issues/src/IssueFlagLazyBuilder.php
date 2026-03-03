<?php

namespace Drupal\nys_issues;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\flag\FlagLinkBuilderInterface;

/**
 * Lazy builder for per-user follow flag links on issue listings.
 *
 * Wrapping the flag link in a lazy builder isolates the user-specific flag
 * state from the globally-cached view output, preventing cache poisoning
 * where one authenticated user's follow state is served to another user.
 */
class IssueFlagLazyBuilder implements TrustedCallbackInterface {

  /**
   * Constructs a new IssueFlagLazyBuilder.
   *
   * @param \Drupal\flag\FlagLinkBuilderInterface $flagLinkBuilder
   *   The flag link builder service.
   */
  public function __construct(
    protected FlagLinkBuilderInterface $flagLinkBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderFlagLink'];
  }

  /**
   * Renders the follow/unfollow flag link for the given taxonomy term.
   *
   * @param string $entity_type_id
   *   The entity type ID (e.g. 'taxonomy_term').
   * @param int|string $entity_id
   *   The entity ID of the issue term.
   *
   * @return array
   *   A render array for the flag link, varying by user.
   */
  public function renderFlagLink(string $entity_type_id, int|string $entity_id): array {
    $build = $this->flagLinkBuilder->build($entity_type_id, $entity_id, 'follow_issue', 'default');

    // Ensure the user cache context is explicit so the render cache creates
    // separate entries per user rather than poisoning a shared entry.
    $build['#cache']['contexts'][] = 'user';

    return $build;
  }

}
