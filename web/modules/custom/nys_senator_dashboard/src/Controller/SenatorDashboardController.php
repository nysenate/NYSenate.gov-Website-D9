<?php

namespace Drupal\nys_senator_dashboard\Controller;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senator_dashboard\Service\SenatorDashboardService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Senator Dashboard routing methods.
 */
class SenatorDashboardController extends ControllerBase {

  /**
   * The senator dashboard service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\SenatorDashboardService
   */
  protected $senatorDashboardService;

  /**
   * Constructs the SenatorDashboardController.
   *
   * @param \Drupal\nys_senator_dashboard\Service\SenatorDashboardService $senatorDashboardService
   *   The senator dashboard service.
   */
  public function __construct(SenatorDashboardService $senatorDashboardService) {
    $this->senatorDashboardService = $senatorDashboardService;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nys_senator_dashboard.service')
    );
  }

  /**
   * Sets the current user's active managed senator and redirects to referrer.
   *
   * @param int $senator_id
   *   The senator ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects the user.
   */
  public function setActiveSenator(int $senator_id, Request $request) {
    // Set the current user's active senator.
    $user_id = $this->currentUser()->id();
    $this->senatorDashboardService->setActiveSenatorForUserId($user_id, $senator_id);

    // Redirect the user to the referring page if internal, home otherwise.
    $referer = $request->headers->get('referer');
    if ($referer) {
      $parsed_url = parse_url($referer);
      $path = $parsed_url['path'] ?? '/';
      if (Url::fromUserInput($path)->isRouted()) {
        return new RedirectResponse($path);
      }
      else {
        return $this->redirect('<front>');
      }
    }
    else {
      return $this->redirect('<front>');
    }
  }

}
