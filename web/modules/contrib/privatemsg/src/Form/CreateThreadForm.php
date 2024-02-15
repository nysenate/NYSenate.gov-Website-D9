<?php

namespace Drupal\privatemsg\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\privatemsg\PrivateMsgService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Private messages form.
 */
class CreateThreadForm extends FormBase {

  /**
   * Common functions.
   */
  protected PrivateMsgService $privateMsgService;

  /**
   * The datetime.time service.
   */
  protected TimeInterface $timeService;

  /**
   * Batch Builder.
   */
  protected BatchBuilder $batchBuilder;

  /**
   * The queue object.
   */
  protected QueueInterface $queue;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateMsgService $privatemsg_service, TimeInterface $time_service, QueueInterface $queue) {
    $this->privateMsgService = $privatemsg_service;
    $this->timeService = $time_service;
    $this->batchBuilder = new BatchBuilder();
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('privatemsg.common'),
      $container->get('datetime.time'),
      $container->get('queue')->get('privatemsg_queue', TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'privatemsg_create_thread';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['to'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('To'),
      '#description' => $this->t('Enter the recipient, separate recipients with commas.'),
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
    ];

    $form['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    ];

    $form['#attributes']['novalidate'] = 'novalidate';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();

    $subject = $form_state->getValue('subject');
    $body = $form_state->getValue('message');
    $body_value = $body['value'];

    if (empty($subject)) {
      $subject = \strip_tags($body_value);
      $subject = \mb_substr($subject, 0, 30);
    }

    $author_id = $current_user->id();
    $format = $body['format'];
    $recipient_id = $form_state->getValue('to');

    $thread_id = $this->privateMsgService->createThread($author_id, $subject, $body_value, $format, $recipient_id);

    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('privatemsg.view_message', ['thread_id' => $thread_id]);
  }

}
