<?php

namespace Drupal\nys_senators\Plugin\NysDashboard;

use Drupal\nys_senators\ManagementPageBase;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Views;

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

    $district_number = NULL;
    if ($senator) {
      $district = $this->helper->loadDistrict($senator);
      $district_number = (int) $district->field_district_number->value;
    }

    $constituent_view = Views::getView('constituents');
    $view = $constituent_view->buildRenderable('by_district', [$district_number]);
    $view2 = views_embed_view('constituents', 'by_district', $district_number);

    return [
      '#theme' => 'nys_senators_management_constituents',
      '#constituent_view' => $view,
    ];

  }

}
