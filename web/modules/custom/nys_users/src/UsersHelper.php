<?php

namespace Drupal\nys_users;

use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Utility functions for NYS User objects.
 *
 * @todo This needs to be a wrapper around the User object, like a Drupal user
 *   object with added functionality.  For now, limit to static functions which
 *   consume a User object as the first parameter.  If an instance is required,
 *   a User object must be resolved during construction (see resolveUser()),
 *   and any non-static functions must act in the context of that object.
 */
class UsersHelper {

  /**
   * Simple wrapper to identify the User entity on which to work.
   *
   * @param mixed|null $user
   *   Can be a user entity, a numeric user ID, or NULL.
   *
   * @return \Drupal\user\Entity\User
   *   If $user was NULL, Drupal's currentUser() is used.
   */
  protected static function resolveUser(mixed $user = NULL): User {
    if (is_null($user)) {
      $user = \Drupal::currentUser()->id();
    }
    if (is_numeric($user)) {
      $user = User::load($user);
    }
    if (!($user instanceof User)) {
      throw new \InvalidArgumentException("resolveUser() requires a User entity, a numeric UID, or NULL as the first parameter");
    }
    return $user;
  }

  /**
   * Builds the "display name" portion of an RFC5322-compliant email address.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getMailName(mixed $user = NULL): string {
    $user = static::resolveUser($user);
    $ufn = $user->field_first_name->value;
    $uln = $user->field_last_name->value;
    return ($ufn && $uln) ? "$ufn $uln" : $user->getEmail();
  }

  /**
   * Gets the Senator (taxonomy_term) assigned to this user's district.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getSenator(mixed $user = NULL): ?Term {
    $user = static::resolveUser($user);
    return $user->field_district->entity->field_senator->entity;
  }

  /**
   * Check if a user is out of state.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   *
   * @todo this could be replaced by "isConstituent()", based on that role.
   */
  public static function isOutOfState(mixed $user = NULL): bool {
    $user = static::resolveUser($user);
    $district = $user->field_district->entity;
    $ret = !($district && $district->id());
    if (!$ret) {
      $state = $user->field_address->value[0]['administrative_area'] ?? '';
      $ret = !($state == 'NY');
    }
    return $ret;
  }

  /**
   * Check if a user is also a senator.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isSenator(mixed $user): bool {
    return static::resolveUser($user)->hasRole('senator');
  }

}
