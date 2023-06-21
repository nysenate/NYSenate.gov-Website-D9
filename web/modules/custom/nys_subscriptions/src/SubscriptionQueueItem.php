<?php

namespace Drupal\nys_subscriptions;

use Drupal\nys_subscriptions\Event\QueueItemReferences;
use Drupal\nys_subscriptions\Event\QueueItemSubscribers;
use Drupal\nys_subscriptions\Event\QueueItemTokens;

/**
 * Wrapper around queue items specifically for nys_subscriptions.
 */
class SubscriptionQueueItem {

  /**
   * The original queue item object (stdClass).
   *
   * @var object
   */
  public object $queueItem;

  /**
   * The queue which owns this item.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionQueue
   */
  public SubscriptionQueue $queue;

  /**
   * Collects common and subscriber-based substitutions for the email request.
   *
   * @var array|array[]
   */
  public array $substitutions = [
    'common' => [],
    'subscribers' => [],
    'template_id' => '',
    'subject' => '',
  ];

  /**
   * Collects references needed for generating substitutions or content.
   *
   * @var array
   */
  public array $references = [];

  /**
   * Collects content to be added to the email request.
   *
   * @var array
   */
  public array $content = [];

  /**
   * The mail key for the Drupal Mail object.  Defaults to the queue name.
   *
   * @var string
   */
  public string $mailKey;

  /**
   * The owning module for the Drupal Mail object.
   */
  public string $mailModule = 'nys_subscriptions';

  /**
   * Passed to Drupal's Mail object after data-loading events have fired.
   *
   * Any event handlers that want to prevent the email from being generated
   * should set this to FALSE.
   *
   * @var bool
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public bool $readyToSend = TRUE;

  /**
   * Constructor.
   */
  public function __construct(object $queue_item, SubscriptionQueue $queue) {
    $this->queueItem = $queue_item;
    $this->queue = $queue;
    $this->mailKey = $this->queue->getName();

    // Set the default subject.
    $this->substitutions['subject'] = $this->queue->getSubject();

    // Dispatch the event to populate references needed for this item.
    /* @phpstan-ignore-next-line */
    $this->queue->dispatcher()
      ->dispatch(new QueueItemReferences($this), Events::QUEUEITEM_REFERENCES);
  }

  /**
   * Magic getter to support stdClass-like access.
   */
  public function __get($name) {
    return $this->queueItem->{$name};
  }

  /**
   * Process this queue item.
   *
   * @throws \Throwable
   */
  public function process(): bool {
    // If OK to send, populate all item-level tokens, i.e., tokens holding the
    // same value for every subscriber.  If the event generates any exceptions,
    // cancel the send and report.
    if ($this->readyToSend) {
      try {
        /* @phpstan-ignore-next-line */
        $this->queue->dispatcher()
          ->dispatch(new QueueItemTokens($this), Events::QUEUEITEM_TOKENS);
      }
      catch (\Throwable $e) {
        $this->readyToSend = FALSE;
        $this->queue->logger->error(
              "Failed to populate item tokens for queue item @id",
              [
                '@id' => $this->queueItem->item_id,
                '@message' => $e->getMessage(),
              ]
          );
      }
    }

    // If OK to send, populate the subscriber tokens (i.e., one dispatch per
    // subscriber).  If the events generate any exceptions, cancel the batch.
    if ($this->readyToSend) {
      try {
        foreach ($this->queueItem->data['recipients'] as $subscriber) {
          /* @phpstan-ignore-next-line */
          $this->queue->dispatcher()
            ->dispatch(new QueueItemSubscribers($this, $subscriber), Events::QUEUEITEM_SUBSCRIBERS);
        }
      }
      catch (\Throwable $e) {
        $this->readyToSend = FALSE;
        $this->queue->logger->error(
              "Failed to populate subscriber tokens for queue item @id",
              [
                '@id' => $this->queueItem->item_id,
                '@message' => $e->getMessage(),
                '@subscriber' => $subscriber ?? 'No Subscriber available',
                '@count' => count($this->queueItem->data['recipients']),
              ]
          );
      }
    }

    return $this->createMail();
  }

  /**
   * Sends a queue item through mail construction.
   *
   * The mail() method requires seven parameters:
   *   - the owning module's name,
   *   - the mail's key.  Using the queue's name as the key, by default.  The
   *     key can be reset by event subscribers,
   *   - the To: address, which is irrelevant to SendGrid API requests,
   *   - the language ID, coming from the Language Manager service,
   *   - mail parameters, which is seeded with this queue item,
   *   - the From: address.  This will be replaced by system variables during
   *     mail construction,
   *   - the "send" flag.  Any process up to now could have set this to false
   *     to cancel the send.
   *
   * @return bool
   *   The "result" key of the final message.  TRUE if the send was successful.
   *
   * @see \Drupal\Core\Mail\MailManager::mail()
   */
  public function createMail(): bool {
    $message = $this->queue->mailer()->mail(
          'nys_subscriptions',
          $this->mailKey,
          '',
          $this->queue->lang()->getCurrentLanguage()->getId(),
          ['queue_item' => $this],
          NULL,
          $this->readyToSend
      );

    // @todo recreate subscription logging facility.
    if ($ret = ($message['result'] ?? FALSE)) {
      // $this->createLogEntry();
    }

    return $ret;

  }

}
