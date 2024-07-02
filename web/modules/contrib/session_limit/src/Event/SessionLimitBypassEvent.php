<?php

namespace Drupal\session_limit\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents an event for bypassing session limits.
 */
class SessionLimitBypassEvent extends Event {

  /**
   * Indicates whether the session limit check should be bypassed.
   *
   * @var bool
   */
  protected $bypass = FALSE;

  /**
   * Informs session limit module that it should bypass the session limit check.
   *
   * Usage:
   *   SessionLimitBypassEvent::setBypass(TRUE);
   *
   * @param bool $bypass
   *   Set to TRUE to bypass. Otherwise don't call this function
   *   if at least one listener for this event calls this function
   *   with a TRUE argument then session limit check is bypassed.
   */
  public function setBypass($bypass) {
    $this->bypass = !empty($bypass) ? TRUE : $this->bypass;
  }

  /**
   * Indicates whether the session limit check should be bypassed.
   *
   * @return bool
   *   TRUE if the session limit check should be bypassed, FALSE otherwise.
   */
  public function shouldBypass() {
    return $this->bypass;
  }

}
