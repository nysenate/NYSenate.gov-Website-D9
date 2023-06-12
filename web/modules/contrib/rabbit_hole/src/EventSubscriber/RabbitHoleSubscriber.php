<?php

namespace Drupal\rabbit_hole\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\rabbit_hole\BehaviorInvoker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Class EventSubscriber.
 *
 * @package Drupal\rabbit_hole
 */
class RabbitHoleSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\rabbit_hole\BehaviorInvoker definition.
   *
   * @var \Drupal\rabbit_hole\BehaviorInvoker
   */
  protected $rabbitHoleBehaviorInvoker;

  /**
   * Constructor.
   */
  public function __construct(BehaviorInvoker $rabbit_hole_behavior_invoker) {
    $this->rabbitHoleBehaviorInvoker = $rabbit_hole_behavior_invoker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'] = ['onRequest', 28];
    $events['kernel.response'] = ['onResponse'];
    return $events;
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * It invokes a rabbit hole behavior on an entity in the request if
   * applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the request.
   */
  public function onRequest(KernelEvent $event) {
    $this->processEvent($event);
  }

  /**
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * Like the onRequest event, it invokes a rabbit hole behavior on an entity in
   * the request if possible. Unlike the onRequest event, it also passes in a
   * response.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the response.
   */
  public function onResponse(KernelEvent $event) {
    $this->processEvent($event);
  }

  /**
   * Process events generically invoking rabbit hole behaviors if necessary.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  private function processEvent(KernelEvent $event) {
    if ($entity = $this->rabbitHoleBehaviorInvoker->getEntity($event)) {
      try {
        $new_response = $this->rabbitHoleBehaviorInvoker->processEntity($entity, $event->getResponse());

        if ($new_response instanceof Response) {
          $event->setResponse($new_response);
        }
      }
      catch (PluginException $e) {
        // Do nothing if we got plugin-related exception.
        // Other exceptions (i.e. AccessDeniedHttpException) should be accepted.
      }
    }
  }

}
