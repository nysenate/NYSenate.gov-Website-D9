<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueDatabaseFactory;
use Drupal\nys_subscriptions\Exception\SubscriptionQueueNotRegistered;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * A wrapper around QueueDatabaseFactory.
 *
 * Subscription queues are registered services.  This manager acts as a service
 * collector for all registered queues.
 */
class SubscriptionQueueManager extends QueueDatabaseFactory implements ContainerInjectionInterface {

  /**
   * Drupal's Event Dispatcher Service.
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
   * {@inheritDoc}
   *
   * Adds the event dispatcher and mail manager services.
   */
  public function __construct(Connection $connection, EventDispatcherInterface $dispatcher, MailManagerInterface $mail, LanguageManagerInterface $language) {
    parent::__construct($connection);
    $this->dispatcher = $dispatcher;
    $this->mail = $mail;
    $this->language = $language;
  }

  /**
   * Factory method for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('database'),
          $container->get('event_dispatcher'),
          $container->get('plugin.manager.mail'),
          $container->get('language_manager')
      );
  }

  /**
   * A cache of registered queues.  SubscriptionQueue[].
   *
   * @var array
   */
  protected array $queues = [];

  /**
   * Service collection hook.
   */
  public function addQueue(SubscriptionQueueInterface $queue) {
    $this->queues[$queue->getName()] = $queue;
  }

  /**
   * Returns an array of all registered queue names.
   */
  public function getQueues(): array {
    return array_keys($this->queues);
  }

  /**
   * Overrides implicit queue creation.
   *
   * Note that this does not prevent subscription queue access via other means,
   * such as \Drupal::queue().
   *
   * @param mixed $name
   *   The name of a registered queue.  Typed as 'mixed' for compatibility with
   *   header for DatabaseQueue::get().
   *
   * @return \Drupal\nys_subscriptions\SubscriptionQueue
   *   The requested queue.
   *
   * @throws \Drupal\nys_subscriptions\Exception\SubscriptionQueueNotRegistered
   *   If $name does not translate to a registered queue.
   */
  public function get($name): SubscriptionQueueInterface {
    $name = (string) $name;
    if (!($ret = $this->queues[$name] ?? NULL)) {
      throw new SubscriptionQueueNotRegistered("'$name' is not a registered Subscription Queue");
    }
    return $ret;
  }

  /**
   * Factory pattern for instantiating queue objects.
   */
  public function queueFactory(string $queue_name, string $queue_subject = ''): SubscriptionQueueInterface {
    return new SubscriptionQueue(
          $queue_name,
          $queue_subject,
          $this->connection,
          $this->dispatcher,
          $this->mail,
          $this->language
      );
  }

}
