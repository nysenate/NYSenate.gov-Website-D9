<?php

namespace Drupal\menu_token\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\menu_token\Service\MenuTokenContextManager;

/**
 * Event Subscriber MenuTokenSubscriber.
 */
class MenuTokenSubscriber implements EventSubscriberInterface {


  protected $currentUser;
  protected $cache;
  protected $contextRepository;
  protected $menuTokenContextManager;

  /**
   * MenuTokenSubscriber constructor.
   *
   * @param \Drupal\menu_token\Service\MenuTokenContextManager $menuTokenContextManager
   *   Menu token context manager.
   */
  public function __construct(MenuTokenContextManager $menuTokenContextManager) {
    $this->menuTokenContextManager = $menuTokenContextManager;
  }

  /**
   * The CONTROLLER event occurs once a controller was found.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The controller event.
   *
   *   For handling a request. Constant KernelEvents::CONTROLLER.
   */
  public function onController(FilterControllerEvent $event) {

    $this->menuTokenContextManager->replaceContextualLinks();

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::CONTROLLER][] = ['onController'];
    return $events;
  }

}
