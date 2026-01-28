<?php

namespace Drupal\nys_newrelic\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to add custom New Relic tracking parameters.
 */
class NewRelicSubscriber implements EventSubscriberInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a NewRelicSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Subscribe to kernel request event with high priority.
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    return $events;
  }

  /**
   * Adds custom New Relic parameters for each request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    // Only process main requests (not subrequests).
    if (!$event->isMainRequest()) {
      return;
    }

    // Check if New Relic PHP extension is loaded.
    if (!extension_loaded('newrelic') || !function_exists('newrelic_add_custom_parameter')) {
      return;
    }

    $user = $this->currentUser;

    // Add authentication status as custom parameter.
    newrelic_add_custom_parameter('user_authenticated', $user->isAuthenticated() ? 'true' : 'false');

    // Add user ID (0 for anonymous).
    newrelic_add_custom_parameter('user_id', (string) $user->id());

    // Add user roles for authenticated users.
    if ($user->isAuthenticated()) {
      $roles = implode(',', $user->getRoles());
      newrelic_add_custom_parameter('user_roles', $roles);
    }
  }

}
