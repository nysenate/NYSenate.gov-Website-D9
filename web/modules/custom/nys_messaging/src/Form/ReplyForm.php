<?php

namespace Drupal\nys_messaging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\private_message\Service\PrivateMessageServiceInterface;
use Drupal\private_message\Service\PrivateMessageThreadManagerInterface;

/**
 * Form class for replying messages.
 */
class ReplyForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Default object for private_message.service service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  protected $privateMessage;

  /**
   * Default object for private_message.thread_manager service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageThreadManagerInterface
   */
  protected $privateMessageThreadManager;

  /**
   * Default object for entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default object for path.current service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Default object for current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The constructor method.
   *
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $private_message
   *   The private message object.
   * @param \Drupal\private_message\Service\PrivateMessageThreadManagerInterface $thread_manager
   *   The private_message.thread_manager object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The path.current object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current_user object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger object.
   */
  public function __construct(
    PrivateMessageServiceInterface $private_message,
    PrivateMessageThreadManagerInterface $thread_manager,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountProxyInterface $current_user,
    MessengerInterface $messenger) {
    $this->privateMessage = $private_message;
    $this->privateMessageThreadManager = $thread_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('private_message.service'),
      $container->get('private_message.thread_manager'),
      $container->get('entity_type.manager'),
      $container->get('path.current'),
      $container->get('current_user'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_messaging_reply_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user_id = NULL, $private_message_id = NULL) {

    $form['pm_id'] = [
      '#type' => 'hidden',
      '#value' => $private_message_id,
    ];

    $form['uid'] = [
      '#type' => 'hidden',
      '#value' => $user_id,
    ];

    $form['textarea'] = [
      '#type' => 'textarea',
      '#title' => t('Reply'),
    ];

    $form['reply'] = [
      '#type' => 'submit',
      '#value' => t('Reply'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $reply = $values['textarea'];

    if (empty($reply)) {
      $form_state->setErrorByName('textarea', t('Reply cannot be blank'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $pmid = $values['pm_id'];
    $uid = $values['uid'];
    $reply = $values['textarea'];

    $loaded_message = $this->entityTypeManager->getStorage('private_message')
      ->load($pmid);

    // @phpstan-ignore-next-line
    $owner = $loaded_message->owner->target_id;

    // Create the reply private message entity.
    $message = PrivateMessage::create([
      'message' => $reply,
      // @phpstan-ignore-next-line
      'field_subject' => $loaded_message->field_subject->value,
    ]);
    $message->field_to = [$owner];

    $message->save();

    // Set the owner of the original message as the recipient.
    $recipient = $this->entityTypeManager->getStorage('user')
      ->load($owner);

    // Save the message to the existing thread.
    $this->privateMessageThreadManager->saveThread(PrivateMessage::load($message->id()), [$recipient]);

    if (!empty($message->id())) {
      $this->messenger->addStatus($this->t('Your message has been sent!'));
    }
  }

}
