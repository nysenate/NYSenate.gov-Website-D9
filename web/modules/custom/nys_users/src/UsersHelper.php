<?php

namespace Drupal\nys_users;

use Drupal\Core\Session\AccountInterface;
use Drupal\nys_senators\SenatorsHelper;
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
   * Note that user ID 1 will always own all roles.
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
    $loaded_user = static::resolveUser($user);
    $is_root = ($loaded_user->id() == 1);
    $user_roles = static::resolveUser($user)->getRoles();
    if (!is_array($check_roles)) {
      $check_roles = [$check_roles];
    }
    return (
      match ($test) {
        self::NYS_USERS_OWNS_ALL => (array_intersect($check_roles, $user_roles) === $check_roles),
        self::NYS_USERS_OWNS_ANY => (bool) (count(array_intersect($check_roles, $user_roles)))
      }
      ) || $is_root;
  }

  /**
   * Check if a user is assigned the senator role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isSenator(mixed $user = NULL): bool {
    return static::hasRoles($user, ['senator']);
  }

  /**
   * Check if a user is assigned the microsite content producer role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isMcp(mixed $user = NULL): bool {
    return static::hasRoles($user, ['microsite_content_producer']);
  }

  /**
   * Check if a user is assigned the legislative correspondent role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isLc(mixed $user = NULL): bool {
    return static::hasRoles($user, ['legislative_correspondent']);
  }

  /**
   * Checks if a user has been assigned LC or MCP roles.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isLcOrMcp(mixed $user = NULL): bool {
    return static::hasRoles(
      $user,
      ['microsite_content_producer', 'legislative_correspondent'],
      static::NYS_USERS_OWNS_ANY
    );
  }

  /**
   * Checks if a user has been assigned administrator or content_admin roles.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function isAdmin(mixed $user = NULL): bool {
    return static::hasRoles(
      $user,
      ['administrator', 'content_admin'],
      static::NYS_USERS_OWNS_ANY
    );
  }

  /**
   * Gets an array of all the senators assigned to a user for management.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getAllManagedSenators(mixed $user = NULL): array {
    return array_unique(
      static::getMcpSenators($user) + static::getLcSenators($user)
    );
  }

  /**
   * Gets an array of all the senators assigned to a user for LC role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getLcSenators(mixed $user = NULL): array {
    $user = static::resolveUser($user);
    $senators = static::isLc($user)
      ? ($user->field_senator_inbox_access->getValue() ?? [])
      : [];
    return array_column($senators, 'target_id', 'target_id');
  }

  /**
   * Gets an array of all the senators assigned to a user for MCP role.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getMcpSenators(mixed $user = NULL): array {
    $user = static::resolveUser($user);
    $senators = static::isMcp($user)
      ? ($user->field_senator_multiref->getValue() ?? [])
      : [];
    return array_column($senators, 'target_id', 'target_id');
  }

  /**
   * Gets a list of all committees a user is assigned to for management.
   *
   * @param \Drupal\user\Entity\User|int|null $user
   *   Either a User entity or the ID of one.  If NULL, current user is used.
   */
  public static function getManagedCommittees(mixed $user = NULL): array {
    $user = static::resolveUser($user);
    $senators = UsersHelper::getAllManagedSenators($user);
    // Get the committee TIDs from senator TIDs based on the committee chair.
    return SenatorsHelper::getChairedCommittees($senators);
  }

}
