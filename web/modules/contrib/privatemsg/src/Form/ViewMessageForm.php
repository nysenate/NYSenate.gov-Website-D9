<?php

namespace Drupal\privatemsg\Form;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\privatemsg\PrivateMsgService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a Private messages form.
 */
class ViewMessageForm extends FormBase {

  /**
   * Common functions.
   */
  protected PrivateMsgService $privateMsgService;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Date Formatter.
   */
  protected DateFormatter $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateMsgService $privatemsg_service, EntityTypeManagerInterface $entity_type_manager, DateFormatter $date_formatter) {
    $this->privateMsgService = $privatemsg_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('privatemsg.common'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'privatemsg_view_message';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $thread_id = $this->getRouteMatch()->getParameter('thread_id');
    $subject = $this->privateMsgService->getThreadSubject($thread_id);
    return $subject;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $thread_id = $this->getRouteMatch()->getParameter('thread_id');
    $current_user = $this->currentUser();
    $mids = $this->privateMsgService->getMessagesIdsByThreadId($thread_id);
    $messages = $this->privateMsgService->getMessagesDataByMids($mids);

    if (empty($messages)) {
      return $this->redirect("privatemsg.messages");
    }

    $access = $this->privateMsgService->checkAccessToThread($thread_id, $current_user->id());
    if (!$access) {
      if (!$current_user->hasPermission('administer site configuration')) {
        throw new NotFoundHttpException();
      }
    }

    $participant_id = $this->privateMsgService->getThreadParticipantByThreadId($thread_id, $current_user->id());
    $participant = $this->entityTypeManager->getStorage('user')->load($participant_id);
    $participant_name = $participant->name->value;

    $title = Markup::create($this->t('Between you and') . ' <a href="/user/' . $participant_id . '">' . $participant_name . '</a>');

    $form['messages'] = [
      '#type' => 'fieldset',
      '#title' => $title,
      '#attached' => [
        'library' => [
          'privatemsg/privatemsg',
        ],
      ],
    ];

    $messages_markup = '';
    foreach ($messages as $message) {
      $date = $this->dateFormatter->format($message['timestamp'], 'medium');
      $author_id = $message['author'];
      $is_new = $message['is_new'];

      if ($author_id == $current_user->id()) {
        $author = $this->t('You');
      }
      else {
        $author_obj = $this->entityTypeManager->getStorage('user')->load($author_id);
        $author = '<a href="/user/' . $author_id . '">' . $author_obj->name->value . '</a>';
      }

      $messages_markup .= '<div id="privatemsg-mid-' . $message['mid'] . '" class="privatemsg-message">';
      $messages_markup .= '<div class="privatemsg-message-column">';
      $messages_markup .= '<div class="privatemsg-message-information">';
      $messages_markup .= '<span class="privatemsg-author-name">' . $author . '</span>';
      $messages_markup .= '<span class="privatemsg-message-date">' . $date . '</span>';

      if ($current_user->hasPermission('privatemsg use messages actions')) {
        $messages_markup .= '<a class="privatemsg-message-delete use-ajax" href="/messages/delete/' . $thread_id . '/' . $message['mid'] . '">' . $this->t('delete') . '</a>';
      }

      if ($is_new > 0 && $author_id != $current_user->id()) {
        $messages_markup .= '<span class="privatemsg-message-new">' . $this->t('new') . '</span>';
      }

      $messages_markup .= '</div>';
      $messages_markup .= '<div class="privatemsg-message-body">';
      $messages_markup .= $message['body'];
      $messages_markup .= '</div></div></div>';
    }

    $form['messages']['item'] = [
      '#type' => 'item',
      '#markup' => $messages_markup,
    ];

    $form['messages']['subject'] = [
      '#type' => 'hidden',
      '#value' => $this->getTitle(),
      '#required' => TRUE,
    ];

    $form['messages']['participant'] = [
      '#type' => 'hidden',
      '#value' => $participant_id,
      '#required' => TRUE,
    ];

    $form['messages']['thread_id'] = [
      '#type' => 'hidden',
      '#value' => $thread_id,
      '#required' => TRUE,
    ];

    $form['messages']['message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];

    $form['messages']['actions'] = [
      '#type' => 'actions',
    ];
    $form['messages']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    ];

    $this->privateMsgService->markThreadAsReadForUser($thread_id, $current_user->id());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser();

    $author_id = $current_user->id();
    $subject = $form_state->getValue('subject');
    $body = $form_state->getValue('message');
    $body_value = $body['value'];
    $format = $body['format'];
    $recipient_id = $form_state->getValue('participant');
    $thread_id = $form_state->getValue('thread_id');

    $access = $this->privateMsgService->checkAccessToThread($thread_id, $current_user->id());
    if (!$access) {
      $form_state->setErrorByName('message', $this->t('You can not have access to write to this thread'));
    }

    $this->privateMsgService->writeMessageToThread($author_id, $subject, $body_value, $format, $recipient_id, $thread_id);

    $this->messenger()->addStatus($this->t('The message has been sent.'));
  }

}
