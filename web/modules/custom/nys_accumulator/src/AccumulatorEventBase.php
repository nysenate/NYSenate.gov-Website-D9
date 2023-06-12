<?php

namespace Drupal\nys_accumulator;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * A base class for accumulator events.
 */
abstract class AccumulatorEventBase extends Event {

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $manager;

  /**
   * A context object, specific to the event being created.
   *
   * @var mixed|null
   */
  public mixed $context;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $manager, mixed $context = NULL) {
    $this->manager = $manager;
    $this->context = $context;
    if (!$this->validateContext()) {
      throw new \InvalidArgumentException("Invalid context for accumulator event " . static::class);
    }
  }

  /**
   * Must return false if context is inappropriate for this event.
   */
  protected function validateContext(): bool {
    return TRUE;
  }

}
