<?php

namespace Drupal\nys_subscriptions\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityInterface;
use Drupal\nys_subscriptions\Entity\Subscription;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing path aliases in pending revisions.
 */
class SubscribeToConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    assert($constraint instanceof SubscribeToConstraint);
    assert($value instanceof Subscription);

    if (!($value->getTarget() instanceof EntityInterface)) {
      $this->context->addViolation($constraint->message);
    }
  }

}
