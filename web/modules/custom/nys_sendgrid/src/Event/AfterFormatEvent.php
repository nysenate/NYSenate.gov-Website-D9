<?php

namespace Drupal\nys_sendgrid\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Defines the nys_sendgrid.after.format event.
 *
 * This event will be dispatched when sending mail, after format() has
 * finished but before mail() is invoked.
 */
class AfterFormatEvent extends Event {

  /**
   * The message about to be sent.
   *
   * @var array
   */
  public array $message;

  /**
   * Constructor.
   *
   * @param array $message
   *   A Drupal message array.
   *
   * @see \Drupal\Core\Mail\MailManager::doMail()
   */
  public function __construct(array $message) {
    $this->message = $message;
  }

}
