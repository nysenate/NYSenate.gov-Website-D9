<?php

namespace Drupal\session_limit\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountInterface;

class SessionLimitDisconnectEvent extends Event {

  /**
   * @var int
   */
  protected $sessionId;

  /**
   * @var SessionLimitCollisionEvent
   */
  protected $collisionEvent;

  /**
   * @var bool
   */
  protected $preventDisconnect = FALSE;

  /**
   * @var string
   */
  protected $message;

  /**
   * SessionLimitCollisionEvent constructor.
   *
   * @param int $sessionId
   * @param SessionLimitCollisionEvent $collisionEvent
   * @param string $message
   */
  public function __construct($sessionId, SessionLimitCollisionEvent $collisionEvent, $message) {
    $this->sessionId = $sessionId;
    $this->collisionEvent = $collisionEvent;
    $this->message = $message;
  }

  /**
   * @return int
   */
  public function getSessionId() {
    return $this->sessionId;
  }

  /**
   * @return SessionLimitCollisionEvent
   */
  public function getCollisionEvent() {
    return $this->collisionEvent;
  }

  /**
   * Call to prevent the session being disconnected.
   *
   * @param bool $state
   *   Set to TRUE to prevent the disconnection (default) any other
   *   value is ignored. Just one listener to the event calling this will
   *   prevent the disconnection.
   */
  public function preventDisconnect($state = TRUE) {
    $this->preventDisconnect = !empty($state) ? TRUE : $this->preventDisconnect;
  }

  /**
   * Determine if the session disconnection should be prevented.
   *
   * @return bool
   */
  public function shouldPreventDisconnect() {
    return $this->preventDisconnect;
  }

  /**
   * Get the message the user will see when their session is ended.
   *
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Set the message the user sees when their session is ended.
   *
   * @param string $message
   * @return $this
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }
}
