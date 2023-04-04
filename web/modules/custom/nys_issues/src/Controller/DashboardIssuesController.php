<?php

namespace Drupal\nys_issues\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardIssuesController extends ControllerBase {

  /**
   * Response for the issues page.
   *
   * Below is for a future iteration of the issues tab.
   *
   * $render = views_embed_view(
   *   'issues_followed_by_users',
   *   'issues_by_constituent'
   * );
   * $content = [
   *   'search_form' => [
   *     '#markup' => 'Want to find new issues to follow?  Explore Link',
   *   ],
   *   'followed_issues' => [
   *     '#markup' => \Drupal::service('renderer')->render($render),
   *   ],
   * ];
   */
  public function followedIssues(): array {

    $content['issues_updates'] = views_embed_view('constituent_updates', 'constituent_all_issue_updates');
    $content['issues_following'] = views_embed_view('explore_issues_tabs', 'constituents_issues_followed');

    return $content;
  }

}
