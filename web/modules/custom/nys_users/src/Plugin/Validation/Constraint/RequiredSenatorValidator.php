<?php

namespace Drupal\nys_users\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates if the Senator field is filled when needed.
 */
class RequiredSenatorValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    // Fetch the entity and try and get its roles.
    $entity = $value->getEntity();
    $roles = $entity->getRoles();

    // Check if the Value is empty if the MCP role is checked.
    if (in_array('microsite_content_producer', $roles, FALSE) && $value->isEmpty()) {

      // Add the required to be filled constraint text defined in this module.
      $this->context->addViolation($constraint->notFilled);

    }
  }

}
