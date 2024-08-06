<?php

namespace Drupal\nys_registration\EventSubscriber\Mailer;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\nys_sendgrid\Event\AfterFormatEvent;
use Drupal\nys_sendgrid\Events;
use Drupal\user\Entity\User;
use SendGrid\Mail\Mail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for registration emails.
 */
class MailSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected Messenger $messenger;

  /**
   * The Drupal messenger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs a MailSubscriber object.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Drupal messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The Drupal error logging service.
   */
  public function __construct(
    Messenger $messenger,
    LoggerChannelFactory $logger,
  ) {
    $this->messenger = $messenger;
    $this->logger = $logger->get('nys_registration');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Events::AFTER_FORMAT => 'registrationMailSendgrid',
    ];
  }

  /**
   * Updates core user registration email to use sendgrid template.
   */
  public function registrationMailSendgrid(AfterFormatEvent $event): void {
    if (
      ($event->message['key'] ?? '') === 'register_no_approval_required'
      && ($event->message['params']['sendgrid_mail'] ?? NULL) instanceof Mail
      && ($event->message['params']['account'] ?? NULL) instanceof User
    ) {
      /** @var \SendGrid\Mail\Mail $mail */
      $mail = &$event->message['params']['sendgrid_mail'];
      /** @var \Drupal\user\Entity\User $account */
      $account = $event->message['params']['account'];

      // Setup sendgrid variables.
      $sendgrid_template_id = '4b3a17d7-d47b-446a-a08e-ab2fbda04794';
      $user_reset_link = user_pass_reset_url($account);
      $mail->setSubject('Your new NYSenate.gov account requires confirmation');
      $substitutions = [
        '%confirm_url%' => $user_reset_link,
      ];

      // Set sendgrid variables.
      try {
        $mail->setTemplateId($sendgrid_template_id);
        $mail->addSubstitutions($substitutions);
      }
      catch (\Throwable $e) {
        $this->messenger->addError('There was an error sending the registration email. Contact the site administrator if the problem persists.');
        $this->logger->error('Failed to update sendgrid mail object with message key: register_no_approval_required.');
        $event->message['send'] = FALSE;
      }
    }
  }

}
