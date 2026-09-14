<?php

namespace Drupal\nys_subscriptions;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueDatabaseFactory;
use Drupal\nys_subscriptions\Exception\SubscriptionQueueNotRegistered;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * A wrapper around QueueDatabaseFactory.
 *
 * Subscription queues are registered services.  This manager acts as the
 * public interface for the queue objects.  A compiler pass generates a map
 * array of all queues and injects it as a constructor parameter.  The map
 * array details the queue name, queue subject, service id, and the class to
 * use for a specific queue.
 */
class SubscriptionQueueManager extends QueueDatabaseFactory {

  use LoggerChannelTrait;

  /**
   * A cache of registered queue instances.
   *
   * All values should implement SubscriptionQueueInterface.
   *
   * @var array
   */
  protected array $queues = [];

  /**
   * A logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritDoc}
   *
   * Adds the event dispatcher, mail manager, and language manager services,
   * necessary to instantiate SubscriptionQueue objects.
   * Adds the $queueServices parameter, which is injected in a compiler pass.
   *
   * @see \Drupal\nys_subscriptions\NysSubscriptionsServiceProvider
   * @see \Drupal\nys_subscriptions\DependencyInjection\Compiler\SubscriptionQueueCollector
   */
  public function __construct(
    Connection $connection,
    protected EventDispatcherInterface $dispatcher,
    protected MailManagerInterface $mail,
    protected LanguageManagerInterface $language,
    protected array $queueServices = [],
  ) {
    parent::__construct($connection);
    $this->logger = $this->getLogger('nys_subscriptions.queue_manager');
  }

  /**
   * Returns an array of all registered queue names.
   */
  public function getQueues(): array {
    return array_keys($this->queueServices);
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
   * @return \Drupal\nys_subscriptions\SubscriptionQueueInterface
   *   The requested queue.
   *
   * @throws \Drupal\nys_subscriptions\Exception\SubscriptionQueueNotRegistered
   *   If $name does not translate to a registered queue.
   */
  public function get($name): SubscriptionQueueInterface {
    $name = (string) $name;

    // If the definition does not exist, throw.
    if (!($service = ($this->queueServices[$name] ?? NULL))) {
      throw new SubscriptionQueueNotRegistered("'$name' is not a registered Subscription Queue");
    }

    // If an instance is not already available, try to create it.
    if (!isset($this->queues[$name])) {
      $class = $service['class'] ?? SubscriptionQueue::class;
      $subject = $service['subject'] ?? SubscriptionQueue::DEFAULT_SUBJECT;
      $this->queues[$name] = $this->queueFactory($name, $subject, $class);
    }

    // Ensure the queue is implementing the expected interface.
    if (!($this->queues[$name] instanceof SubscriptionQueueInterface)) {
      throw new SubscriptionQueueNotRegistered("'$name' does not implement SubscriptionQueueInterface");
    }

    return $this->queues[$name];
  }

  /**
   * Factory pattern for instantiating queue objects.
   */
  protected function queueFactory(string $queue_name, string $queue_subject = '', string $queue_class = SubscriptionQueue::class): SubscriptionQueueInterface {
    return new $queue_class(
      $queue_name,
      $queue_subject,
      $this->connection,
      $this->dispatcher,
      $this->mail,
      $this->language
    );
  }

}
