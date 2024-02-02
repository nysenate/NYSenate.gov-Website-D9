<?php

namespace Drupal\privatemsg;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Common functions service.
 */
class PrivateMsgService {

  use StringTranslationTrait;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The datetime.time service.
   */
  protected TimeInterface $timeService;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mail manager service.
   */
  protected MailManagerInterface $mailManager;

  /**
   * Request Stack.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, TimeInterface $time_service, EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, RequestStack $request_stack) {
    $this->database = $database;
    $this->timeService = $time_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Get threads by user id.
   *
   * @param int $user_id
   *   User id.
   */
  public function getThreadsByUserId(int $user_id): array {
    // If current user a recipient.
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['thread_id', 'is_new']);
    $query->fields('pmm', ['subject', 'timestamp']);
    $query->leftJoin('pm_message', 'pmm', 'pmi.mid = pmm.mid');
    $query->condition('pmi.recipient', $user_id);
    $query->orderBy('timestamp', 'DESC');
    $threads = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // If current user an author.
    $query = $this->database->select('pm_message', 'pmm');
    $query->fields('pmm', ['subject', 'timestamp']);
    $query->fields('pmi', ['thread_id']);
    $query->leftJoin('pm_index', 'pmi', 'pmi.mid = pmm.mid');
    $query->condition('pmm.author', $user_id);
    $query->orderBy('timestamp', 'DESC');
    $messages = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    // Merge user threads and messages.
    $merged = \array_merge($threads, $messages);

    // Remove thread_id duplicates.
    $threads_unique = \array_intersect_key($merged, \array_unique(\array_column($merged, 'thread_id')));
    // Set thread_id as array key.
    $threads_unique = \array_combine(\array_column($threads_unique, 'thread_id'), $threads_unique);

    // Sort threads by timestamp.
    $col = \array_column($threads_unique, 'timestamp');
    \array_multisort($col, \SORT_DESC, $threads_unique);

    return $threads_unique;
  }

  /**
   * Get thread participant by thread id.
   *
   * @param int $thread_id
   *   Thread id.
   * @param int $user_id
   *   User id.
   */
  public function getThreadParticipantByThreadId(int $thread_id, int $user_id): ?int {
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['recipient']);
    $query->condition('pmi.thread_id', $thread_id);
    $query->condition('pmi.recipient', $user_id, '!=');
    $recipients = $query->execute()->fetchAssoc();

    if (!$recipients) {
      $query = $this->database->select('pm_index', 'pmi');
      $query->fields('pmm', ['author']);
      $query->join('pm_message', 'pmm', 'pmi.mid = pmm.mid');
      $query->condition('pmi.thread_id', $thread_id);
      $query->condition('pmi.deleted', 0);
      $recipients = $query->execute()->fetchAssoc();
      $recipients = \reset($recipients);
      return $recipients;
    }

    $recipients = \reset($recipients);
    return $recipients;
  }

  /**
   * Check user access to thread.
   *
   * @param int $thread_id
   *   Thread id.
   * @param int $user_id
   *   User id.
   */
  public function checkAccessToThread(int $thread_id, int $user_id): bool {
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['mid']);
    $query->condition('pmi.thread_id', $thread_id);
    $query->condition('pmi.recipient', $user_id);
    $mids = $query->distinct()->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if ((bool) $mids) {
      return TRUE;
    }

    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['mid']);
    $query->condition('pmi.thread_id', $thread_id);
    $mids = $query->distinct()->execute()->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($mids as $mid) {
      $query = $this->database->select('pm_message', 'pmm');
      $query->fields('pmm', ['author']);
      $query->condition('pmm.mid', $mid['mid']);
      $author_id = $query->execute()->fetchCol();
      $author_id = \reset($author_id);

      if ($author_id == $user_id) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get messages ids by thread id.
   *
   * @param int $thread_id
   *   Thread id.
   */
  public function getMessagesIdsByThreadId(int $thread_id): array {
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['mid']);
    $query->condition('pmi.thread_id', $thread_id);
    $query->orderBy('pmi.mid');
    $mids = $query->distinct()->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $mids;
  }

  /**
   * Mark thread as read.
   *
   * @param int $thread_id
   *   Thread id.
   * @param int $user_id
   *   User id.
   */
  public function markThreadAsReadForUser(int $thread_id, int $user_id): void {
    $query = $this->database->update('pm_index');
    $query->fields([
      'is_new' => 0,
    ]);
    $query->condition('recipient', $user_id);
    $query->condition('thread_id', $thread_id);
    $query->execute();
  }

  /**
   * Mark thread as unread.
   *
   * @param int $thread_id
   *   Thread id.
   * @param int $user_id
   *   User id.
   */
  public function markThreadAsUnreadForUser(int $thread_id, int $user_id): void {
    $query = $this->database->update('pm_index');
    $query->fields([
      'is_new' => $this->timeService->getCurrentTime(),
    ]);
    $query->condition('recipient', $user_id);
    $query->condition('thread_id', $thread_id);
    $query->execute();
  }

  /**
   * Delete thread.
   *
   * @param int $thread_id
   *   Thread id.
   */
  public function deleteThread(int $thread_id): void {
    $mids = $this->getMessagesIdsByThreadId($thread_id);

    foreach ($mids as $mid) {
      $mid = \reset($mid);
      $query = $this->database->delete('pm_message');
      $query->condition('mid', $mid);
      $query->execute();
    }

    $query = $this->database->delete('pm_index');
    $query->condition('thread_id', $thread_id);
    $query->execute();
  }

  /**
   * Get messages data by mids.
   *
   * @param array $mids
   *   Messages ids.
   */
  public function getMessagesDataByMids(array $mids): array {
    $data = [];

    foreach ($mids as $mid) {
      $mid = \reset($mid);
      $query = $this->database->select('pm_message', 'pmm');
      $query->fields('pmm', ['mid', 'author', 'body', 'timestamp']);
      $query->fields('pmi', ['is_new']);
      $query->join('pm_index', 'pmi', 'pmi.mid = pmm.mid');
      $query->condition('pmm.mid', $mid);
      $temp = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      $data[$mid] = \reset($temp);
    }

    return $data;
  }

  /**
   * Get thread subject.
   *
   * @param int $thread_id
   *   Thread id.
   */
  public function getThreadSubject(int $thread_id): string {
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmm', ['subject']);
    $query->join('pm_message', 'pmm', 'pmi.mid = pmm.mid');
    $query->condition('pmi.thread_id', $thread_id);
    $query->range(0, 1);
    $subject = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $subject = \reset($subject);
    $subject = \reset($subject);
    return $subject;
  }

  /**
   * Get last thread id.
   */
  public function getLastThreadId(): int {
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['thread_id']);
    $query->range(0, 1);
    $query->orderBy('pmi.thread_id', 'DESC');
    $thread_id = $query->execute()->fetchCol();
    $thread_id = \reset($thread_id);
    return $thread_id;
  }

  /**
   * Create thread (write new message).
   *
   * @param int $author_id
   *   Author id.
   * @param string $subject
   *   Subject.
   * @param string $body
   *   Message body.
   * @param string $format
   *   Text format.
   * @param int $recipient_id
   *   Recipient id.
   * @param bool $send_email
   *   Send email notification.
   */
  public function createThread(int $author_id, string $subject, string $body, string $format, int $recipient_id, bool $send_email = TRUE): int {
    $thread_id = $this->getLastThreadId() + 1;

    $this->writeMessageToThread($author_id, $subject, $body, $format, $recipient_id, $thread_id, $send_email);

    return $thread_id;
  }

  /**
   * Write new message.
   *
   * @param int $author_id
   *   Author id.
   * @param string $subject
   *   Subject.
   * @param string $body
   *   Message body.
   * @param string $format
   *   Text format.
   * @param int $recipient_id
   *   Recipient id.
   * @param int $thread_id
   *   Thread id.
   * @param bool $send_email
   *   Send email notification.
   */
  public function writeMessageToThread(int $author_id, string $subject, string $body, string $format, int $recipient_id, int $thread_id, bool $send_email = TRUE): void {
    $timestamp = $this->timeService->getCurrentTime();

    $query = $this->database->insert('pm_message');
    $query->fields([
      'author' => $author_id,
      'subject' => $subject,
      'body' => $body,
      'format' => $format,
      'timestamp' => $timestamp,
      'has_tokens' => 0,
    ]);
    $mid = $query->execute();

    $query = $this->database->insert('pm_index');
    $query->fields([
      'mid' => $mid,
      'thread_id' => $thread_id,
      'recipient' => $recipient_id,
      'is_new' => $timestamp,
      'deleted' => 0,
      'type' => 'user',
    ]);
    $query->execute();

    // Undelete thread for recipient.
    $query = $this->database->update('pm_index');
    $query->fields([
      'deleted' => 0,
    ]);
    $query->condition('recipient', $recipient_id);
    $query->condition('thread_id', $thread_id);
    $query->execute();

    // Send email notification if user set checkbox.
    if ($send_email) {
      $this->sendEmailNotification($recipient_id, $thread_id);
    }
  }

  /**
   * Check is enabled email notification for user.
   *
   * @param int $user_id
   *   User id.
   */
  public function isEnabledEmailNotification(int $user_id): int {
    $query = $this->database->select('pm_email_notify', 'pmen');
    $query->fields('pmen', ['email_notify_is_enabled']);
    $query->condition('user_id', $user_id);
    $user_settings = $query->execute()->fetchCol();

    if ($user_settings) {
      $user_settings = \reset($user_settings);
      return (int) $user_settings;
    }

    return 0;
  }

  /**
   * Enable or disable email notification for user.
   *
   * @param int $user_id
   *   User id.
   * @param int $new_status
   *   New status of user settings.
   */
  public function enableDisableEmailNotification(int $user_id, int $new_status): void {
    $query = $this->database->select('pm_email_notify', 'pmen');
    $query->fields('pmen', ['email_notify_is_enabled']);
    $query->condition('user_id', $user_id);
    $user_settings = $query->execute()->fetchCol();

    // If record exist.
    if ($user_settings) {
      $query = $this->database->update('pm_email_notify');
      $query->fields([
        'email_notify_is_enabled' => $new_status,
      ]);
      $query->condition('user_id', $user_id);
      $query->execute();
    }
    else {
      $query = $this->database->insert('pm_email_notify');
      $query->fields([
        'user_id' => $user_id,
        'email_notify_is_enabled' => $new_status,
      ]);
      $query->execute();
    }
  }

  /**
   * Send email notification.
   *
   * @param int $recipient_id
   *   Recipient id.
   * @param int $thread_id
   *   Thread id.
   */
  public function sendEmailNotification(int $recipient_id, int $thread_id): void {
    if ($this->isEnabledEmailNotification($recipient_id)) {
      $host = $this->requestStack->getCurrentRequest()->getHost();
      $params['subject'] = $this->t('New private message');
      $params['message'] = $this->t('You have received a new private message. To read your message, follow this link: http://%host/messages/view/%thread_id', [
        '%host' => $host,
        '%thread_id' => $thread_id,
      ]);
      $recipient = $this->entityTypeManager->getStorage('user')->load($recipient_id);
      if (!empty($recipient->getEmail())) {
        $this->mailManager->mail('privatemsg', 'privatemsg_mail', $recipient->getEmail(), 'ru', $params);
      }
    }
  }

  /**
   * Get unread threads count.
   *
   * @param int $user_id
   *   User id.
   */
  public function getUnreadCountForUser(int $user_id): int {
    $query = $this->database->select('pm_index', 'pmi');
    $query->fields('pmi', ['thread_id']);
    $query->condition('is_new', '0', '!=');
    $query->condition('recipient', $user_id);
    $new_messages = $query->distinct()->countQuery()->execute()->fetchField();
    return $new_messages;
  }

  /**
   * Get all users uids.
   */
  public function getAllUsersUids(int $author_id): array {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $query = $userStorage->getQuery();
    $uids = $query
      ->condition('status', '1')
      ->condition('uid', $author_id, '!=')
      ->execute();
    return $uids;
  }

}
