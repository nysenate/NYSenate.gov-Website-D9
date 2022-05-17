<?php

namespace Drupal\nys_sendgrid\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Defines the nys_sendgrid.after.send event.
 *
 * This event will be dispatched when sending mail, after mail() has finished.
 * The API response referenced at $message['params']['sendgrid_mail']->response.
 */
class AfterSendEvent extends Event {

  /**
   * The message about to be sent.
   *
   * @var array
   */
  protected array $message;

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

  /**
   * Gets the message array.
   */
  public function getMessage(): array {
    return $this->message;
  }

}
