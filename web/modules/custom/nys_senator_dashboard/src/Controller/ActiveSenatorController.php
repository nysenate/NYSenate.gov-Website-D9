<?php

namespace Drupal\nys_senator_dashboard\Controller;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides routing methods for managing an MCP's or LC's active senator(s).
 */
class ActiveSenatorController extends ControllerBase {

  /**
   * The senator dashboard service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * Constructs the SenatorDashboardController.
   *
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators service.
   */
  public function __construct(ManagedSenatorsHandler $managed_senators_handler) {
    $this->managedSenatorsHandler = $managed_senators_handler;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nys_senator_dashboard.managed_senators_handler')
    );
  }

  /**
   * Sets the current user's active managed senator and redirects to referrer.
   *
   * @param int $senator_tid
   *   The senator TID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects the user.
   */
  public function updateActiveSenator(int $senator_tid, Request $request) {
    // Set the current user's active senator.
    $this->managedSenatorsHandler->updateActiveSenator($this->currentUser()->id(), $senator_tid);

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
