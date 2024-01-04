<?php

namespace Drupal\nys_users;

use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Utility functions for NYS User objects.
 *
 * This is intended to be a wrapper around the User object.  For now, limit to
 * static functions which consume a User object as the first parameter.  Can
 * also be refactored as a service if an instance is required.
 */
class UsersHelper {

  /**
   * Constant indicating a test if a user owns all of the tested roles.
   */
  const NYS_USERS_OWNS_ALL = 1;

  /**
   * Constant indicating a test if a user owns any of the tested roles.
   */
  const NYS_USERS_OWNS_ANY = 2;

  /**
   * Simple wrapper to identify the User entity on which to work.
   *
   * @param mixed|null $user
   *   Can be any object implementing AccountInterface, a numeric user ID,
   *   or NULL.
   *
   * @return \Drupal\user\Entity\User
   *   If $user was NULL, Drupal's currentUser() is used.
   */
  public static function resolveUser(mixed $user = NULL): User {
    if (is_null($user)) {
      $user = \Drupal::currentUser()->id();
    }
    if ($user instanceof AccountInterface) {
      $user = $user->id();
    }
    if (is_numeric($user)) {
      $user = User::load($user);
    }
    if (!($user instanceof User)) {
      throw new \InvalidArgumentException("resolveUser() requires an AccountInterface, a numeric id, or NULL as the first parameter");
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
    $ufn = $user->field_first_name->value ?? '';
    $uln = $user->field_last_name->value ?? '';
    return ($ufn && $uln) ? "$ufn $uln" : $user->getEmail();
  }

  /**
   * Gets the Senator (taxonomy_term) assigned to this user's district.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   Returns NULL if there was any problem resolving the senator.
   */
  public static function getSenator(mixed $user = NULL): ?Term {
    $user = static::resolveUser($user);
    $district = $user->field_district->entity ?? NULL;
    return ($district instanceof Term)
        ? ($district->field_senator->entity ?? NULL)
        : NULL;
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
    $district = $user->field_district->entity ?? NULL;
    $ret = !($district && $district->id());
    if (!$ret) {
      $state = $user->field_address[0]->administrative_area ?? '';
      $ret = !($state == 'NY');
    }
    return $ret;
  }

  /**
   * Check if a user reference owns a particular role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   * @param array|string $check_roles
   *   The name of a role, or an array of names.
   * @param int $test
   *   The type of test to conduct.
   *
   * @see static::NYS_USERS_OWNS_ALL
   * @see static::NYS_USERS_OWNS_ANY
   */
  public static function hasRoles(mixed $user, array|string $check_roles, int $test = self::NYS_USERS_OWNS_ALL): bool {
    $user_roles = static::resolveUser($user)->getRoles();
    if (!is_array($check_roles)) {
      $check_roles = [$check_roles];
    }
    return match ($test) {
      self::NYS_USERS_OWNS_ALL => (array_intersect($check_roles, $user_roles) === $check_roles),
            self::NYS_USERS_OWNS_ANY => (bool) (count(array_intersect($check_roles, $user_roles)))
    };
  }

  /**
   * Check if a user is assigned the senator role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isSenator(mixed $user): bool {
    return static::hasRoles($user, ['senator']);
  }

  /**
   * Check if a user is assigned the microsite content producer role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isMcp(mixed $user): bool {
    return static::hasRoles($user, ['microsite_content_producer']);
  }

  /**
   * Check if a user is assigned the legislative correspondent role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isLc(mixed $user): bool {
    return static::hasRoles($user, ['legislative_correspondent']);
  }

  /**
   * Checks if a user has been assigned LC or MCP roles.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isLcOrMcp(mixed $user): bool {
    return static::hasRoles(
          $user,
          ['microsite_content_producer', 'legislative_correspondent'],
          static::NYS_USERS_OWNS_ANY
      );
  }

  /**
   * Gets a list of all the senators assigned to a user for management.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getUserSenatorManagement(mixed $user): array {
    $user = static::resolveUser($user);
    $senator_tids = [];

    // If the user doesn't have the field, or it's empty, return an empty array.
    if (!$user->hasField('field_senator_multiref') || $user->field_senator_multiref->isEmpty()) {
      return $senator_tids;
    }
    // Otherwise, return an array of senator term IDs.
    $senators = $user->field_senator_multiref->getValue();
    return array_column($senators, 'target_id', 'target_id');
  }

  /**
   * Gets a list of committees by senator tids found in field_chair.
   *
   * @param array $tids
   *   Senator term IDs.
   */
  public static function getCommitteesBySenators(array $tids): array {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $query = $storage->getQuery()
      ->condition('vid', 'committees')
      ->condition('field_chair', $tids, 'IN')
      ->addTag('prevent_recursion')
      ->accessCheck(TRUE);

    return $query->execute();
  }

}
