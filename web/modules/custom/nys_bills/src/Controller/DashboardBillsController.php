<?php

namespace Drupal\nys_bills\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardBillsController extends ControllerBase {

  /**
   * Response for the bills page.
   */
  public function followedBills(): array {
    $content['bills_updates'] = views_embed_view('constituent_updates', 'constituent_all_bill_updates');
    $content['bills_voted_on'] = views_embed_view('constituent_bills', 'constituent_bills_voted_on');

    return $content;
  }

}
