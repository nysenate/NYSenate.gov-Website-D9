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
    $constituent_view->setArguments([$district_number]);
    $constituent_view->setDisplay('default');
    $constituent_view->preExecute();
    $constituent_view->execute();

    return [
      '#theme' => 'nys_senators_management_constituents',
      '#attached' => ['library' => ['nysenate_theme/dashboard']],
      '#constituent_view' => $constituent_view->render(),
    ];

  }

}
