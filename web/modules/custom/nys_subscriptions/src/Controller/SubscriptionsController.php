<?php

namespace Drupal\nys_subsciptions\Controller;

use Drupal\nys_bills\BillsHelper;

/**
 * Route controller for School Form submissions.
 */
class SubscriptionsController extends ControllerBase {

  /**
   * NYS Bills Helper service.
   *
   * @var \Drupal\nys_bills\BillsHelper
   */
  protected BillsHelper $billsHelper;

  /**
   * Constructor.
   */
  public function __construct(BillsHelper $billsHelper) {
    $this->billsHelper = $billsHelper;
  }

  /**
   * Controller method to subscribe .
   */
  public function confirmCreateSubscription($sid) {

  }

  /**
   * Controller method to unsubscribe .
   */
  public function confirmDeleteSubscription($sid) {

  }

  /**
   * Controller method to unsubscribe .
   */
  public function confirmGlobalUnsubscribe($sid) {

  }

}
