<?php

namespace Drupal\nys_committees\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardCommitteesController extends ControllerBase {

  /**
   * Response for the committees page.
   */
  public function followedCommittees(): array {
    $content['committees_updates'] = views_embed_view('constituent_updates', 'constituent_all_committees_updates');
    $content['committees_following'] = views_embed_view('constituent_committees_following', 'block');

    return $content;
  }

}
