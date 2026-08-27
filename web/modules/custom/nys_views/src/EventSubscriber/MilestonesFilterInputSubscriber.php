<?php

namespace Drupal\nys_views\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Normalizes malformed "milestones" query input before form building.
 *
 * BillCurrentCycleMilestonesFilter exposes a checkboxes-type filter with
 * the identifier "milestones", which Drupal's Form API expects as an
 * array (one entry per checked box, e.g. milestones[passed_senate]=...).
 * A hand-edited or bookmarked URL using the plain scalar form instead
 * (?milestones=passed_senate) crashes uncaught during form building -
 * \Drupal\Core\Form\FormBuilder::handleInputElement() tries to write a
 * nested key onto the submitted value and fails with a LogicException,
 * since you can't index into a string like an array. This never happens
 * from the real rendered checkboxes (they always submit the array form),
 * only from someone constructing the URL by hand.
 *
 * Runs on the request itself, before routing/form building, since the
 * crash happens too early in Views' own render pipeline for the filter
 * plugin's own acceptExposedInput() to ever get a chance to intervene.
 */
class MilestonesFilterInputSubscriber implements EventSubscriberInterface {

  /**
   * The exposed filter identifier this subscriber guards.
   */
  const IDENTIFIER = 'milestones';

  /**
   * Wraps a scalar "milestones" query value into Form API's expected array.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function normalizeMilestonesInput(RequestEvent $event): void {
    $request = $event->getRequest();
    // InputBag::get() throws on array values (the well-formed case for a
    // checkboxes filter), so the raw parameter is read via all() instead,
    // which performs no scalar/array enforcement.
    $value = $request->query->all()[self::IDENTIFIER] ?? NULL;

    if ($value !== NULL && !is_array($value)) {
      $request->query->set(self::IDENTIFIER, [$value => $value]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => 'normalizeMilestonesInput',
    ];
  }

}
