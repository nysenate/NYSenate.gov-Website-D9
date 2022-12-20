<?php

namespace Drupal\nys_subscriptions\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for changing path aliases in pending revisions.
 *
 * @Constraint(
 *   id = "SubscriptionSubscribeTo",
 *   label = @Translation("Subscription target", context = "Validation"),
 * )
 */
class SubscribeToConstraint extends Constraint {

  /**
   * Message for an invalid subscription target.
   *
   * @var string
   */
  public string $message = 'Subscription target is not an entity.';

}
