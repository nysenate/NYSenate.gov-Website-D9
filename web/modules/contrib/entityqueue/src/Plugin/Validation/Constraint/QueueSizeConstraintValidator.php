<?php

namespace Drupal\entityqueue\Plugin\Validation\Constraint;

use Drupal\entityqueue\EntitySubqueueInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the QueueSize constraint.
 */
class QueueSizeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    assert($entity instanceof EntitySubqueueInterface);
    assert($constraint instanceof QueueSizeConstraint);

    $queue = $entity->getQueue();
    $min_size = $queue->getMinimumSize();
    $max_size = $queue->getMaximumSize();
    $act_as_queue = $queue->getActAsQueue();
    $number_of_items = count($entity->items);

    // Do not allow less items than the minimum size.
    if ($min_size && $number_of_items < $min_size) {
      $this->context->buildViolation($constraint->messageMinSize, ['%min_size' => $min_size])
        ->addViolation();
    }
    // Do not allow more items than the maximum size if the queue is not
    // configured to act a simple list.
    elseif (!$act_as_queue && $max_size && $number_of_items > $max_size) {
      $this->context->buildViolation($constraint->messageMaxSize, ['%max_size' => $max_size])
        ->addViolation();
    }
  }

}
