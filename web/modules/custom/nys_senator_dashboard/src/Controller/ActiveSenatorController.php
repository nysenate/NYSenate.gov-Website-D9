<?php

namespace Drupal\nys_senator_dashboard\Controller;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senator_dashboard\Service\ActiveSenatorManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides routing methods for managing an MCP's or LC's active senator(s).
 */
class ActiveSenatorController extends ControllerBase {

  /**
   * The senator dashboard service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ActiveSenatorManager
   */
  protected ActiveSenatorManager $activeSenatorManager;

  /**
   * Constructs the SenatorDashboardController.
   *
   * @param \Drupal\nys_senator_dashboard\Service\ActiveSenatorManager $activeSenatorManager
   *   The senator dashboard service.
   */
  public function __construct(ActiveSenatorManager $activeSenatorManager) {
    $this->activeSenatorManager = $activeSenatorManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nys_senator_dashboard.active_senator_manager')
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
    $this->activeSenatorManager->setActiveSenatorForUserId($user_id, $senator_id);

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
