<?php

namespace Drupal\comments_ban\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the current user is not banned from create comments.
 *
 * @Constraint(
 *   id = "userCommentBanned",
 *   label = @Translation("Comments ban", context = "Validation"),
 *   type = "string"
 * )
 */
class CommentsBanConstraint extends Constraint {

  /**
   * @var string
   *   Message for banned users.
   */
  public $userCommentBanned = 'You\'re not allowed to post this comment';

}
