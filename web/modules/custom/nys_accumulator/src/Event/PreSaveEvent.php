<?php

namespace Drupal\nys_accumulator\Event;

use Drupal\nys_accumulator\AccumulatorEventBase;

/**
 * Defines the nys_accumulator.pre_save event.
 *
 * This event will be dispatched after all data point resolutions have been
 * completed, before the record is saved.
 */
class PreSaveEvent extends AccumulatorEventBase {

  /**
   * {@inheritDoc}
   */
  protected function validateContext(): bool {
    return is_array($this->context);
  }

}
