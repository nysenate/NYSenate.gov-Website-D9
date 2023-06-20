<?php

namespace Drupal\nys_senators\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class McpEventSubscriber for adding custom redirects control to terms.
 *
 * @package Drupal\nys_senators\EventSubscriber
 *
 * Removes non-admin/mcp access to Senator Terms.
 */
class McpEventSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * A Senator Helper Service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected $senatorHelper;

  /**
   * McpEventSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(RequestStack $request_stack, CurrentRouteMatch $current_route_match, AccountInterface $current_user) {
    $this->requestStack = $request_stack;
    $this->currentRouteMatch = $current_route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function redirectFromSenators(RequestEvent $event) {

    // Grab route name for checking.
    $routeName = $this->currentRouteMatch->getRouteName();

    // Check if a non-admin is looking at a Senator term.
    if ($routeName === 'entity.taxonomy_term.canonical'
          && ($term = $this->currentRouteMatch->getParameter('taxonomy_term'))->bundle() === 'senator'
      ) {

      $senator_helper = \Drupal::service('nys_senators.senators_helper');

      if (!$senator_helper->senatorUserIsAdmin($this->currentUser)) {

        // Load current user in full.
        $user = User::load($this->currentUser->id());
        $has_mcp_access = FALSE;

        // Check if user has access to term.
        if ($user->hasField('field_senator_multiref')
              && !empty($senators = array_column($user->field_senator_multiref->getValue(), 'target_id'))
              && in_array($term->id(), $senators, FALSE)
          ) {
          // User is MCP with access to term.
          $has_mcp_access = TRUE;
        }

        // If no MCP access then redirect.
        if (!$has_mcp_access) {
          // Redirecting Home.
          $response = new TrustedRedirectResponse('/', 302);
          $event->setResponse($response);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      KernelEvents::REQUEST => 'redirectFromSenators',
    ];
    return $events;
  }

}
