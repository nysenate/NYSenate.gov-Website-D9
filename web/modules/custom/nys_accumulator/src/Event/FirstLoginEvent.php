<?php

namespace Drupal\nys_accumulator\Event;

use Drupal\nys_accumulator\AccumulatorEventBase;
use Drupal\user\Entity\User;

/**
 * Defines the nys_accumulator.first_login event.
 *
 * This event is dispatched based on detection in hook_user_login().
 *
 * @see nys_accumulator_user_login()
 */
class FirstLoginEvent extends AccumulatorEventBase {

  /**
   * {@inheritDoc}
   */
  protected function validateContext(): bool {
    return ($this->context instanceof User);
  }

}
