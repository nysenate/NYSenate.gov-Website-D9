<?php

namespace Drupal\nys_messaging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\private_message\Entity\PrivateMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class for forwarding messages.
 */
class ForwardForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Default object for plugin.manager.mail service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The constructor method.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger object.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The plugin.manager.mail object.
   */
  public function __construct(MessengerInterface $messenger, MailManagerInterface $mail_manager) {
    $this->messenger = $messenger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('messenger'),
          $container->get('plugin.manager.mail'),
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_messaging_forward_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user_id = NULL, $private_message_id = NULL) {

    $private_message = PrivateMessage::load($private_message_id);

    /**
     * @var \Drupal\user\Entity\User $owner
*/
    $owner = $private_message->owner->entity;
    $from = $owner->name->value;
    $subject = $private_message->field_subject->value;
    $message = $private_message->message->value;

    $forwarded_message = "---\r\n" . "from: " . $from . "\r\n\r\n" . "subject: " . $subject . "\r\n\r\n\r\n" . $message . "\r\n\r\n" . "--";

    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => $user_id,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#value' => $forwarded_message,
    ];

    $form['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (empty($values['email'])) {
      $form_state->setErrorByName('email', $this->t('The email field is required.'));
    }

    if (empty($values['subject'])) {
      $form_state->setErrorByName('subject', $this->t('The subject field is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $module = 'nys_messaging';
    $key = 'forward_message';
    $to = $values['email'];
    $params['message'] = $values['message'];
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;

    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== TRUE) {
      $this->messenger->addError($this->t('There was a problem sending your message and it was not sent.'));
    }
    else {
      $this->messenger->addStatus($this->t('Your message has been sent.'));
    }
  }

}
