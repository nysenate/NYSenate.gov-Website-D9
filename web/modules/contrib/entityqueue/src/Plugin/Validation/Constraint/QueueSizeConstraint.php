<?php

namespace Drupal\entityqueue\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating entity subqueue sizes.
 *
 * @Constraint(
 *   id = "QueueSize",
 *   label = @Translation("Queue size", context = "Validation"),
 *   type = "entity:entity_subqueue"
 * )
 */
class QueueSizeConstraint extends Constraint {

  /**
   * Message shown when the minimum number of queue items is not reached.
   *
   * @var string
   */
  public $messageMinSize = 'This queue can not hold less than %min_size items.';

  /**
   * Message shown when the maximum number of queue items is exceed.
   *
   * @var string
   */
  public $messageMaxSize = 'This queue can not hold more than %max_size items.';

}
