<?php

namespace Drupal\private_message\Service;

/**
 * The Private Message Ban manager service interface.
 */
interface PrivateMessageBanManagerInterface {

  /**
   * Check if the user is banned by the current user.
   *
   * @param int $user_id
   *   ID of the target user to test.
   *
   * @return bool
   *   TRUE if the given user is banned by the current user. FALSE otherwise.
   */
  public function isBanned(int $user_id): bool;

  /**
   * Checks if the current user is banned by the user.
   *
   * @param int $user_id
   *   ID of the user to test.
   *
   * @return bool
   *   TRUE if the current user is banned by the given user. FALSE otherwise.
   */
  public function isCurrentUserBannedByUser(int $user_id): bool;

  /**
   * Returns list of users banned by a user with given ID.
   *
   * @param int $user_id
   *   ID of the user to test.
   *
   * @return array
   *   An array of user ids that the user has banned.
   */
  public function getBannedUsers(int $user_id): array;

  /**
   * Adds a user to the current user's banned users list.
   *
   * @param int $user_id
   *   ID of the user to ban.
   */
  public function banUser(int $user_id);

  /**
   * Removes a banned user from their banned users list.
   *
   * @param int $user_id
   *   ID of the user to unban.
   */
  public function unbanUser(int $user_id);

}
