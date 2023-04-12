<?php

namespace Drupal\nys_petitions\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardPetitionsController extends ControllerBase {

  /**
   * Response for the committees page.
   */
  public function signedPetitions(): array {
    $content['petitions_signed'] = views_embed_view('constituent_petitions_and_questionnaires', 'constituent_petitions_signed');

    return $content;
  }

}
