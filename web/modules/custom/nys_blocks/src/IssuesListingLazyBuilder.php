<?php

namespace Drupal\nys_blocks;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\views\Views;

/**
 * Lazy builder for the issues listing view.
 *
 * This allows the view to be rendered as a BigPipe placeholder,
 * enabling page caching while still showing user-specific flag counts.
 */
class IssuesListingLazyBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderIssuesListing'];
  }

  /**
   * Renders the issues listing view.
   *
   * @param string $display_id
   *   The view display ID to render.
   *
   * @return array
   *   A render array for the issues listing view.
   */
  public function renderIssuesListing(string $display_id = 'news_issues_listing'): array {
    $view = Views::getView('issues_listings');
    if (!$view) {
      return [];
    }

    $view->setDisplay($display_id);
    $view->preExecute();
    $view->execute();

    $build = $view->render();

    // Override the cache metadata to allow page caching.
    // The view's cache metadata includes max-age: 0 due to flag_counts sort,
    // but since this is rendered via BigPipe placeholder, we can safely
    // set a reasonable cache lifetime. The content will be re-rendered
    // per request via the lazy builder.
    // Keep the cache tags for proper invalidation when issues change.
    $build['#cache'] = [
      'tags' => $build['#cache']['tags'] ?? ['config:views.view.issues_listings'],
      'contexts' => ['languages:language_interface', 'theme'],
      'max-age' => Cache::PERMANENT,
    ];

    return $build;
  }

}
