<?php

namespace Drupal\session_limit\Event;

use Drupal\Core\Session\AccountInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines an event for handling session limit collisions.
 */
class SessionLimitCollisionEvent extends Event {

  /**
   * The session ID.
   *
   * @var int
   */
  protected $sessionId;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The maximum user session.
   *
   * @var int
   */
  protected $userMaxSessions;

  /**
   * The user active session.
   *
   * @var int
   */
  protected $userActiveSessions;

  /**
   * Constructs a \Drupal\session_limit\Event\SessionLimitCollisionEvent object.
   *
   * @param int $sessionId
   *   The session ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param int $userActiveSessions
   *   The user active sessions.
   * @param int $userMaxSessions
   *   The user max session.
   */
  public function __construct($sessionId, AccountInterface $account, $userActiveSessions, $userMaxSessions) {
    $this->sessionId = $sessionId;
    $this->account = $account;
    $this->userActiveSessions = $userActiveSessions;
    $this->userMaxSessions = $userMaxSessions;
  }

  /**
   * Get the session ID.
   *
   * @return int
   *   The session ID.
   */
  public function getSessionId() {
    return $this->sessionId;
  }

  /**
   * Get the maximum user session.
   *
   * @return int
   *   The maximum user session.
   */
  public function getUserMaxSessions() {
    return $this->userMaxSessions;
  }

  /**
   * Get the user active session ID.
   *
   * @return int
   *   The user active session ID.
   */
  public function getUserActiveSessions() {
    return $this->userActiveSessions;
  }

  /**
   * Get the user account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user account object.
   */
  public function getAccount() {
    return $this->account;
  }

}
