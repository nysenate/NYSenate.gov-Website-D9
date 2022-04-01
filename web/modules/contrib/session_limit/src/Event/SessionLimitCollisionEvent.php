<?php

namespace Drupal\session_limit\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountInterface;

class SessionLimitCollisionEvent extends Event {

  /**
   * @var int
   */
  protected $sessionId;

  /**
   * @var AccountInterface
   */
  protected $account;

  /**
   * @var int
   */
  protected $userMaxSessions;

  /**
   * @var int
   */
  protected $userActiveSessions;

  /**
   * SessionLimitCollisionEvent constructor.
   *
   * @param int $sessionId
   * @param AccountInterface $account
   * @param int $userActiveSessions
   * @param int $userMaxSessions
   */
  public function __construct($sessionId, $account, $userActiveSessions, $userMaxSessions) {
    $this->sessionId = $sessionId;
    $this->account = $account;
    $this->userActiveSessions = $userActiveSessions;
    $this->userMaxSessions = $userMaxSessions;
  }

  /**
   * @return int
   */
  public function getSessionId() {
    return $this->sessionId;
  }

  /**
   * @return int
   */
  public function getUserMaxSessions() {
    return $this->userMaxSessions;
  }

  /**
   * @return int
   */
  public function getUserActiveSessions() {
    return $this->userActiveSessions;
  }

  /**
   * @return AccountInterface
   */
  public function getAccount() {
    return $this->account;
  }

}
