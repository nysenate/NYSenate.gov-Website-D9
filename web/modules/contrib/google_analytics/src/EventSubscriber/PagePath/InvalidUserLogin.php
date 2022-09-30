<?php

namespace Drupal\google_analytics\EventSubscriber\PagePath;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\google_analytics\Event\PagePathEvent;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Content Translation to custom URL
 */
class InvalidUserLogin implements EventSubscriberInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Invalid User Login Path
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The current request stack.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route
   *   The current route.
   */
  public function __construct(RequestStack $request, CurrentRouteMatch $current_route) {
    $this->request = $request;
    $this->currentRoute = $current_route;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::PAGE_PATH][] = ['onPagePath', 100];
    return $events;
  }

  /**
   * Adds error pages to the page path.
   *
   * @param \Drupal\google_analytics\Event\PagePathEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onPagePath(PagePathEvent $event) {
    // #2693595: User has entered an invalid login and clicked on forgot
    // password link. This link contains the username or email address and may
    // get send to Google if we do not override it. Override only if 'name'
    // query param exists. Last custom url condition, this need to win.
    //
    // URLs to protect are:
    // - user/password?name=username
    // - user/password?name=foo@example.com
    $base_path = base_path();
    $request = $this->request->getCurrentRequest();
    if ($this->currentRoute->getRouteName() == 'user.pass' && $request->query->has('name')) {
      $event->setPagePath('"' . $base_path . 'user/password"');
      $event->stopPropagation();
    }
  }
}
