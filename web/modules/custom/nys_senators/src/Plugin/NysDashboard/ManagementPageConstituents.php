<?php

namespace Drupal\nys_senators\Plugin\NysDashboard;

use Drupal\nys_senators\ManagementPageBase;
use Drupal\taxonomy\TermInterface;

/**
 * Creates the overview page for the senator management dashboard.
 *
 * @SenatorManagementPage(
 *   id = "constituents"
 * )
 */
class ManagementPageConstituents extends ManagementPageBase {

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): array {

    $view = views_embed_view('constituents', 'default', '48');

    return [
      'constituent_view' => $view,
    ];

  }

}
