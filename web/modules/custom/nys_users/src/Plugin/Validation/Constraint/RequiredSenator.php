<?php

namespace Drupal\nys_users\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the Senator is filled out when it is Required.
 *
 * @Constraint(
 *   id = "RequiredSenator",
 *   label = @Translation("Require Senator", context = "Validation"),
 *   type = "string"
 * )
 */
class RequiredSenator extends Constraint {

  /**
   * The message that will be shown if the Senator does not have a value.
   *
   * @var string
   */
  public $notFilled = 'The Senator field must have a value.';

}
