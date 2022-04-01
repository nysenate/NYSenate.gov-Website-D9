<?php

namespace Drupal\scheduler;

use Drupal\node\NodeInterface;

/**
 * Wraps a scheduler event for event listeners.
 */
class SchedulerEvent extends EventBase {

  /**
   * Gets node object.
   *
   * @return \Drupal\node\NodeInterface
   *   The node object that caused the event to fire.
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * Sets the node object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object that caused the event to fire.
   */
  public function setNode(NodeInterface $node) {
    $this->node = $node;
  }

}
