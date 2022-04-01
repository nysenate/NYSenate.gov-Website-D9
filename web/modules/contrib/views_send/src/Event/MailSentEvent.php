<?php

namespace Drupal\views_send\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Event that is fired when an e-mail has been sent.
 */
class MailSentEvent extends Event {

  const EVENT_NAME = 'views_send_email_sent';

  /**
   * The message.
   *
   * @var \Drupal\Core\TypedData\Plugin\DataType\Map
   */
  public $message;

  /**
   * Constructs the object.
   *
   * @param array $message
   *   The message.
   */
  public function __construct($message) {
    /*
    FIXME
    $this->message = new Map();
    $this->message->setValue($message);
    */
  }

}
