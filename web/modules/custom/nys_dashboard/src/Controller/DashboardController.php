<?php

namespace Drupal\nys_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller class for nys_dashboard module.
 */
class DashboardController extends ControllerBase {

  /**
   * Path to display the issues views.
   */
  public function issues() {
    $content['issues_updates'] = views_embed_view('constituent_updates', 'constituent_all_issue_updates');
    $content['issues_following'] = views_embed_view('explore_issues_tabs', 'constituents_issues_followed');

    return $content;
  }

  /**
   * Path to display the committee views.
   */
  public function committees() {
    $content['committees_updates'] = views_embed_view('constituent_updates', 'constituent_all_committees_updates');
    $content['committees_following'] = views_embed_view('constituent_committees_following', 'block');

    return $content;
  }

}
