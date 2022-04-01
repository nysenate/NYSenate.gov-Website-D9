<?php

namespace Drupal\scheduler;

// Drupal\Component\EventDispatcher\Event was introduced in Drupal core 9.1 to
// assist with deprecations and the transition to Symfony 5.
// @todo Remove this when core 9.1 is the lowest supported version.
// @see https://www.drupal.org/project/scheduler/issues/3166688
if (!class_exists('Drupal\Component\EventDispatcher\Event')) {
  class_alias('Symfony\Component\EventDispatcher\Event', 'Drupal\Component\EventDispatcher\Event');
}

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

/**
 * Base class on which all Scheduler events are extended.
 */
class EventBase extends Event {

  /**
   * The node which is being processed.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which is being processed.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

}
