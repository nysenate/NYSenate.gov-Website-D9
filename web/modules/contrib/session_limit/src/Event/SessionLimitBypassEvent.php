<?php

namespace Drupal\session_limit\Event;

use Symfony\Component\EventDispatcher\Event;

class SessionLimitBypassEvent extends Event {

  /**
   * @var bool
   */
  protected $bypass = FALSE;

  /**
   * Tell the session limit module you want to bypass session limit check.
   *
   * usage:
   *   SessionLimitBypassEvent->bypass(TRUE);
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
   * @return bool
   *   True if the session limit check should be bypassed.
   */
  public function shouldBypass() {
    return $this->bypass;
  }

}
