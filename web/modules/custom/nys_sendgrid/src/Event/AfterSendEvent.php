<?php

namespace Drupal\nys_sendgrid\Event;

use Drupal\Component\EventDispatcher\Event;
use SendGrid\Response;

/**
 * Defines the nys_sendgrid.after.send event.
 *
 * This event will be dispatched when sending mail, after mail() has finished.
 */
class AfterSendEvent extends Event {

  /**
   * The Drupal array of the message which was sent.
   *
   * @var array
   */
  protected array $message;

  /**
   * The Sendgrid API Response object.
   *
   * @var \SendGrid\Response|null
   */
  protected ?Response $response = NULL;

  /**
   * Constructor.
   *
   * @param array $message
   *   A Drupal message array.
   * @param \SendGrid\Response|null $response
   *   The Response object from the Sendgrid API call.  Under certain failure
   *   conditions, this may be NULL.
   *
   * @see \Drupal\Core\Mail\MailManager::doMail()
   */
  public function __construct(array $message, ?Response $response = NULL) {
    $this->response = $response;
    $this->message = $message;
  }

  /**
   * Gets the message array.
   */
  public function getMessage(): array {
    return $this->message;
  }

  /**
   * Gets the API Response object.
   */
  public function getResponse(): ?Response {
    return $this->response;
  }

}
