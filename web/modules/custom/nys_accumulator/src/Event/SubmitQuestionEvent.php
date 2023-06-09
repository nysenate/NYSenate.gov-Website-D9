<?php

namespace Drupal\nys_accumulator\Event;

use Drupal\nys_accumulator\AccumulatorEventBase;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Defines the nys_accumulator.submit_question event.
 *
 * This event is dispatched based on detection in hook_entity_presave().
 *
 * @see nys_accumulator_entity_presave()
 */
class SubmitQuestionEvent extends AccumulatorEventBase {

  /**
   * {@inheritDoc}
   */
  protected function validateContext(): bool {
    return ($this->context instanceof WebformSubmission);
  }

}
