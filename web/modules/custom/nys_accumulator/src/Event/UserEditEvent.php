<?php

namespace Drupal\nys_accumulator\Event;

use Drupal\nys_accumulator\AccumulatorEventBase;
use Drupal\user\Entity\User;

/**
 * Defines the nys_accumulator.user_edit event.
 *
 * This event is dispatched based on detection in hook_entity_presave().
 *
 * @see nys_accumulator_entity_presave()
 */
class UserEditEvent extends AccumulatorEventBase {

  /**
   * {@inheritDoc}
   */
  protected function validateContext(): bool {
    return ($this->context instanceof User);
  }

}
