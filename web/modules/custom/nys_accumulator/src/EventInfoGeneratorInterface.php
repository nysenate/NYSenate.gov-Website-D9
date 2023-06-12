<?php

namespace Drupal\nys_accumulator;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the interface for an event info generation plugin.
 */
interface EventInfoGeneratorInterface {

  /**
   * Builds the event_info array expected by accumulator records.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $source
   *   An object capable of providing the meta-data used to build an event
   *   info array.
   *
   * @throws \InvalidArgumentException
   */
  public function build(ContentEntityBase $source): array;

}
