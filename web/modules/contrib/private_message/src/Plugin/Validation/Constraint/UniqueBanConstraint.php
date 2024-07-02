<?php

namespace Drupal\private_message\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for unique bans.
 *
 * @Constraint(
 *   id = "UniquePrivateMessageBan",
 *   label = @Translation("Unique ban.", context = "Validation"),
 * )
 */
class UniqueBanConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public string $message = 'The user %user is already banned.';

}
