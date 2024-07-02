<?php

namespace Drupal\session_limit\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines an event for handling session disconnections.
 */
class SessionLimitDisconnectEvent extends Event {

  /**
   * The session ID.
   *
   * @var int
   */
  protected $sessionId;

  /**
   * The collision event.
   *
   * @var SessionLimitCollisionEvent
   */
  protected $collisionEvent;

  /**
   * The prevent disconnection status.
   *
   * @var bool
   */
  protected $preventDisconnect = FALSE;

  /**
   * The message to display.
   *
   * @var string
   */
  protected $message;

  /**
   * Constructs a new SessionLimitCollisionEvent object.
   *
   * @param int $sessionId
   *   The session ID.
   * @param SessionLimitCollisionEvent $collisionEvent
   *   The collision event.
   * @param string $message
   *   The message.
   */
  public function __construct($sessionId, SessionLimitCollisionEvent $collisionEvent, $message) {
    $this->sessionId = $sessionId;
    $this->collisionEvent = $collisionEvent;
    $this->message = $message;
  }

  /**
   * Gets the session ID.
   *
   * @return int
   *   The session ID.
   */
  public function getSessionId() {
    return $this->sessionId;
  }

  /**
   * Gets the collision event.
   *
   * @return SessionLimitCollisionEvent
   *   The collision event.
   */
  public function getCollisionEvent() {
    return $this->collisionEvent;
  }

  /**
   * Prevents the session disconnection.
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
   * Determines if the session disconnection should be prevented.
   *
   * @return bool
   *   Returns bool.
   */
  public function shouldPreventDisconnect() {
    return $this->preventDisconnect;
  }

  /**
   * Gets the message users see when their session is ended.
   *
   * @return string
   *   The message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Sets the message users see when their session is ended.
   *
   * @param string $message
   *   The message.
   *
   * @return $this
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

}
