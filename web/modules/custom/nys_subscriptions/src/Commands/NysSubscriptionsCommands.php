<?php

namespace Drupal\nys_subscriptions\Commands;

use Drupal\nys_subscriptions\SubscriptionQueueManager;
use Drush\Commands\DrushCommands;

/**
 * Drush command file for nys_subscriptions.
 */
class NysSubscriptionsCommands extends DrushCommands {

  /**
   * The default maximum execution time in seconds for queue processing.
   */
  const MAX_PROCESS_TIME = 240;

  /**
   * The Subscription Queue Manager service.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionQueueManager
   */
  protected SubscriptionQueueManager $queueManager;

  /**
   * Constructor.
   */
  public function __construct(SubscriptionQueueManager $queueManager) {
    parent::__construct();
    $this->queueManager = $queueManager;
  }

  /**
   * Resolves a list of requested queues into a list of registered queues.
   *
   * @param string $queues
   *   A comma-delimited list of queue names.
   *
   * @return array
   *   Array of registered queues found from the requested list.
   */
  protected function resolveQueues(string $queues): array {
    $all_queues = $this->queueManager->getQueues();
    $listed = explode(',', $queues);
    $ret = [];
    if (count($listed)) {
      foreach ($listed as $queue) {
        if (in_array($queue, $all_queues)) {
          $ret[] = $queue;
        }
        else {
          $this->logger()
            ->warning("Ignoring queue '@name' because it is not registered", ['@name' => $queue]);
        }
      }
    }
    else {
      $ret = $all_queues;
    }
    return $ret;
  }

  /**
   * Processes pending items for a list of queues.
   *
   * If no queues are specified, all registered queues will be processed.  Each
   * queue specified must be registered.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @return int
   *   A Drush return code.
   *
   * @option queues
   *   A comma-delimited list of names of queues to be processed.
   * @usage nys_subscriptions:processQueues
   *   Processes all registered queues.
   * @usage nysub-pq --queues=bill_notifications,site_news
   *   Processes only the bill_notifications and site_news queues, as long as
   *   they are registered.
   *
   * @command nys_subscriptions:processQueues
   *
   * @aliases nysub-pq
   */
  public function processQueues(array $options = [
    'queues' => '',
    'max_runtime' => NULL,
  ]): int {
    $ret = DRUSH_SUCCESS;
    $queues = $this->resolveQueues($options['queues'] ?? '');
    foreach ($queues as $one_queue) {
      $this->logger()->info('Processing queue: @name', ['@name' => $one_queue]);
      $bedtime = (int) ($options['max_runtime'] ?? self::MAX_PROCESS_TIME);
      try {
        $queue = $this->queueManager->get($one_queue);
        $results = $queue->process($bedtime);
        $this->logger()
          ->info(
                  "Completed processing for queue @name, @success success, @fail fail, @skip skipped",
                  [
                    '@success' => $results->getSuccess(),
                    '@fail' => $results->getFail(),
                    '@skip' => $results->getSkipped(),
                  ]
              );
      }
      catch (\Throwable $e) {
        $this->logger()
          ->error("Queue @name failed to process, message:\n@msg", ['@msg' => $e->getMessage()]);
        $ret = DRUSH_APPLICATION_ERROR;
      }
    }
    return $ret;
  }

}
