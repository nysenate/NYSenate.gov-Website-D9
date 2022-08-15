<?php

namespace Drupal\nys_subscriptions\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for changing path aliases in pending revisions.
 *
 * @Constraint(
 *   id = "SubscriptionTypeNotValid",
 *   label = @Translation("Subscription type", context = "Validation"),
 * )
 */
class TypeNotValidConstraint extends Constraint {

  /**
   * Message for a blank subscription type.
   *
   * @var string
   */
  public string $notEmpty = 'Subscription type must not be empty.';

  /**
   * Message for a malformed subscription type.
   *
   * @var string
   */
  public string $requireMachineName = 'Subscription type must be a proper machine name ([a-zA-Z0-9_]+)';

}
