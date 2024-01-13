<?php

namespace Drupal\nys_accumulator\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\nys_accumulator\AccumulatorEntry;
use Drupal\nys_accumulator\AccumulatorEventBase;
use Drupal\nys_accumulator\Events;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Management wrapper to assist with accumulator entry creation.
 */
class Accumulator implements ContainerInjectionInterface {

  /**
   * Drupal's Event Dispatcher Service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * Drupal's Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Drupal's Current Request service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $helper;

  /**
   * Constructor.
   */
  public function __construct(EventDispatcherInterface $dispatcher, Connection $database, RequestStack $request, SenatorsHelper $helper) {
    $this->dispatcher = $dispatcher;
    $this->database = $database;
    $this->request = $request->getCurrentRequest();
    $this->helper = $helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('database'),
      $container->get('current_request'),
      $container->get('nys_senators.senators_helper')
    );
  }

  /**
   * Creates a new, uninitialized entry.
   */
  public function newEntry(): AccumulatorEntry {
    return new AccumulatorEntry(
      $this->dispatcher,
      $this->database,
      $this->request,
      $this->helper
    );
  }

  /**
   * Creates and returns a new, initialized entry.
   *
   * @param string $type
   *   A message type.  Defaults to 'misc'.
   * @param string $action
   *   A message action.  Defaults to an empty string.
   * @param mixed $user
   *   An object implementing AccountInterface, an integer representing a user
   *   id, or NULL, which loads the current authenticated user.
   * @param \Drupal\taxonomy\Entity\Term|null $target
   *   A taxonomy term belonging to the senator or district bundles.  Note that
   *   NULL will resolve to the district of the resolved user.
   */
  public function createEntry(string $type = 'misc', string $action = '', mixed $user = NULL, ?Term $target = NULL): AccumulatorEntry {
    return $this->newEntry()
      ->setType($type)
      ->setAction($action)
      ->setUser($user)
      ->setTarget($target);
  }

  /**
   * Wrapper to create an accumulator-based event.
   *
   * @param string $name
   *   The event name, matching a constant in nys_accumulator\Events.
   * @param mixed $context
   *   A context, generally the focus of the event, e.g., user, bill, etc.
   *
   * @return \Drupal\nys_accumulator\AccumulatorEventBase|null
   *   Returns NULL on any error during instantiation.
   */
  public static function createEvent(string $name, mixed $context = NULL): ?AccumulatorEventBase {
    $all_events = Events::getEvents();
    try {
      $ret = '\\' . $all_events[strtoupper($name)];
      return new ($ret)(\Drupal::entityTypeManager(), $context);
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Dispatches an accumulator event by name.
   *
   * @param string $name
   *   The name of the event.
   * @param mixed|null $context
   *   A context object for the event.
   *
   * @see \Drupal\nys_accumulator\Events
   */
  public function dispatch(string $name, mixed $context = NULL): void {
    $event = static::createEvent($name, $context);
    if ($event) {
      $this->dispatcher->dispatch($event);
    }
  }

}
