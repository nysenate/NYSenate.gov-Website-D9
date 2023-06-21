<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * A wrapper around DatabaseQueue.
 *
 * Adds runtime defaults, service references to pass through to a queue item,
 * a configurable subject text, and exposes the queue name.
 */
class SubscriptionQueue extends DatabaseQueue implements SubscriptionQueueInterface {

  use LoggerChannelTrait;

  /**
   * Default maximum queue processing time, in seconds.
   *
   * This is the fallback default for nys_subscriptions.settings.max_runtime.
   */
  const MAX_RUNTIME_DEFAULT = 240;

  /**
   * The maximum recipients per queue item.
   */
  const MAX_RECIPIENTS_DEFAULT = 1000;

  /**
   * A default subject line, if not populated by service config.
   */
  const DEFAULT_SUBJECT = 'Automated Notification';

  /**
   * A logger channel.
   */
  public LoggerInterface $logger;

  /**
   * Drupal's Event Dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * Drupal's Mail Manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mail;

  /**
   * Drupal's Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $language;

  /**
   * A default subject for any items in this queue.
   *
   * @var string
   */
  protected string $subject;

  /**
   * {@inheritDoc}
   *
   * Adds an optional queue subject, and a few Drupal services.
   */
  public function __construct(string $name, string $subject, Connection $connection, EventDispatcherInterface $dispatcher, MailManagerInterface $mail, LanguageManagerInterface $language) {
    parent::__construct($name, $connection);
    $this->logger = $this->getLogger('nys_subscriptions');
    $this->dispatcher = $dispatcher;
    $this->mail = $mail;
    $this->language = $language;
    $this->setSubject($subject);
  }

  /**
   * Exposes the queue name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Getter for Dispatcher.
   */
  public function dispatcher(): EventDispatcherInterface {
    return $this->dispatcher;
  }

  /**
   * {@inheritDoc}
   *
   * Wraps the return into a SubscriptionQueueItem object.
   */
  public function claimItem($lease_time = 30): ?SubscriptionQueueItem {
    $ret = parent::claimItem($lease_time);
    return $ret ? new SubscriptionQueueItem($ret, $this) : NULL;
  }

  /**
   * {@inheritDoc}
   *
   * To transparently handle SubscriptionQueueItem objects.
   *
   * @throws \Exception
   */
  public function createItem($data): int {
    $item = ($data instanceof SubscriptionQueueItem) ? $data->queueItem : $data;
    return parent::createItem($item);
  }

  /**
   * Processes as many queue items as time allows.
   *
   * @param int $time_limit
   *   The maximum number of seconds processing should continue.  An item
   *   started before the limit was reached will finish its processing.
   *
   * @return \Drupal\nys_subscriptions\QueueProcessResult
   *   The results.
   *
   * @todo The new model allows for subscriptions to any entity type.  The
   *   suppression engine needs to be refactored to allow for identifying
   *   suppressed entities by type and ID.
   */
  public function process(int $time_limit = 0): QueueProcessResult {

    $result = new QueueProcessResult();
    $fails = [];

    // Set the max timestamp for execution time, if needed.
    $bedtime = $time_limit ? time() + $time_limit : 0;

    // Get each item in the queue and process() it.
    while ($item = $this->claimItem()) {
      try {
        // @todo Implement suppression here.
        // The return will be as from drupal_mail()
        $one_result = $item->process();
      }
      catch (\Throwable $e) {
        // The item will throw an exception if something in the item data
        // prevents processing.  API failures (e.g., SendGrid returns a
        // 404) are considered "normal" failures.
        $one_result = NULL;
        $this->logger
          ->warning(
                  "Exception while processing item @id", [
                    '@id' => $item->item_id ?? 'No ID found',
                    '@msg' => $e->getMessage(),
                  ]
              );
      }

      // If processing was successful, delete the queue item.
      if ($one_result) {
        $result->addSuccess();
        $this->deleteItem($item);
      }
      // Reinstate the fails for the next try.
      else {
        $result->addFail();
        $fails[] = $item;
      }

      // If we're past our bedtime, leave.
      if ($bedtime && ($bedtime < time())) {
        $this->logger->info("Queue processing stopped due to maximum runtime.");
        break;
      }
    }

    // Release all the failures back into queue.
    foreach ($fails as $val) {
      $this->releaseItem($val);
    }

    return $result;
  }

  /**
   * Getter for Mail Manager service.
   */
  public function mailer(): MailManagerInterface {
    return $this->mail;
  }

  /**
   * Getter for Language Manager service.
   */
  public function lang(): LanguageManagerInterface {
    return $this->language;
  }

  /**
   * Sets the subject, with a default, if necessary.
   */
  public function setSubject(string $subject = self::DEFAULT_SUBJECT) {
    $this->subject = $subject ?: self::DEFAULT_SUBJECT;
  }

  /**
   * Gets the subject.
   */
  public function getSubject(): string {
    return $this->subject;
  }

}
