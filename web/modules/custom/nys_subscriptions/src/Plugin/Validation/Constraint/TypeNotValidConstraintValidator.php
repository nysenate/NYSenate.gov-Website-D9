<?php

namespace Drupal\nys_subscriptions\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemList;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing path aliases in pending revisions.
 */
class TypeNotValidConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * Ensures the subscription type is a "safe" machine name.
   */
  public function validate($value, Constraint $constraint) {
    assert($constraint instanceof TypeNotValidConstraint);
    assert($value instanceof FieldItemList);

    $string = $value->value;

    if (!$string) {
      $this->context->addViolation($constraint->notEmpty);
    }
    elseif (preg_match('/\W/', $string)) {
      $this->context->addViolation($constraint->requireMachineName);
    }
  }

}
