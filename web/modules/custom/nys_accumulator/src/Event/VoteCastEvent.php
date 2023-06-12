<?php

namespace Drupal\nys_accumulator\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\nys_accumulator\AccumulatorEventBase;
use Drupal\votingapi\Entity\Vote;

/**
 * Defines the nys_accumulator.vote_cast event.
 *
 * This event is dispatched based on detection in hook_entity_presave().
 *
 * @see nys_accumulator_entity_presave()
 */
class VoteCastEvent extends AccumulatorEventBase {

  /**
   * {@inheritDoc}
   */
  protected function validateContext(): bool {
    return ($this->context instanceof Vote);
  }

  /**
   * Attempts to load the entity receiving the vote.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns NULL if the entity cannot be found, or if an error occurs.
   */
  public function getVotedEntity(): ?EntityInterface {
    try {
      $ret = $this->manager->getStorage($this->context->getVotedEntityType())
        ->load($this->context->getVotedEntityId());
    }
    catch (\Throwable) {
      $ret = NULL;
    }
    return $ret;
  }

}
