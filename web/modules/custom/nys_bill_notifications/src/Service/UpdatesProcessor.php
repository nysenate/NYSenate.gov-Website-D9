<?php

namespace Drupal\nys_bill_notifications\Service;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\node\Entity\Node;
use Drupal\nys_openleg_api\ResponsePluginInterface;
use Drupal\nys_openleg_api\Service\Api;
use Drupal\nys_subscriptions\Entity\Subscription;
use Drupal\nys_subscriptions\SubscriptionQueue;
use Drupal\nys_subscriptions\SubscriptionQueueInterface;
use Drupal\nys_subscriptions\SubscriptionQueueManager;
use Drupal\taxonomy\Entity\Term;

/**
 * Service used to generate subscription queue items based on OL bill updates.
 */
class UpdatesProcessor {

  /**
   * Preconfigured logging channel for Openleg API.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Config object for nys_bill_notifications.settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * NYS Openleg API Manager service.
   *
   * @var \Drupal\nys_openleg_api\Service\Api
   */
  protected Api $apiManager;

  /**
   * The plugin manager for bill notification update tests.
   *
   * @var \Drupal\nys_bill_notifications\Service\BillTestManager
   */
  protected BillTestManager $tester;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Drupal Queue object.
   *
   * @var \Drupal\nys_subscriptions\SubscriptionQueueInterface
   */
  protected SubscriptionQueueInterface $queue;

  /**
   * Constructor.
   *
   * @throws \Drupal\nys_subscriptions\Exception\SubscriptionQueueNotRegistered
   */
  public function __construct(LoggerChannel $logger, ConfigFactory $config, Api $apiManager, EntityTypeManager $entityTypeManager, BillTestManager $tester, SubscriptionQueueManager $queueManager) {
    $this->logger = $logger;
    $this->config = $config->get('nys_bill_notifications.settings');
    $this->apiManager = $apiManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->tester = $tester;
    $this->queue = $queueManager->get('bill_notifications');
  }

  /**
   * Calculates the full print number of a bill from an update block.
   *
   * @param object $update
   *   A JSON-decoded OpenLeg bill update block.
   *
   * @return string
   *   The full print number, "<session>-<basePrintNo>".  Returns a blank
   *   string if either data point is empty.
   */
  public function getBasePrintFromUpdate(object $update): string {
    $session = $update->id->session ?? '';
    $print = $update->id->basePrintNo ?? '';
    return ($session && $print) ? "$session-$print" : '';
  }

  /**
   * Gets a list of bill updates from Openleg.
   *
   * Timestamp ranges for Openleg are exclusive to inclusive: (from, to]
   *
   * @param mixed $time_from
   *   A timestamp, preferably epoch time or OpenLeg standard format.
   *   This value is exclusive.
   * @param mixed $time_to
   *   A timestamp, preferably epoch time or OpenLeg standard format.
   *   This value is inclusive.
   * @param array $params
   *   Query string parameters to add to the API request.
   *
   * @return \Drupal\nys_openleg_api\ResponsePluginBase
   *   The Response object from Openleg.
   */
  protected function retrieveUpdates(mixed $time_from, mixed $time_to, array $params = []): ResponsePluginInterface {
    return $this->apiManager->getUpdates('bill', $time_from, $time_to, ['detail' => 'true'] + $params);
  }

  /**
   * Tests an array of updates, returning an array of results.
   *
   * @param array $updates
   *   An array of update blocks from OpenLeg.
   *
   * @return array
   *   An array of results, keyed by bill print number.  Each element is an
   *   array of MatchResults objects (one bill can have multiple updates
   *   within a time range).
   */
  public function testUpdates(array $updates): array {
    $matches = [];
    foreach ($updates as $update) {
      $result = $this->tester->matchUpdate($update);
      if ($result && $result->getMatchCount()) {
        $id = $result->getFullPrint();
        if (!array_key_exists($id, $matches)) {
          $matches[$id] = [];
        }
        $matches[$id][] = $result;
      }
    }
    return $matches;
  }

  /**
   * Loads a bill node by title (full print number).
   *
   * @param string $print_num
   *   A full print number, e.g., 2021-S123.
   *
   * @return \Drupal\node\Entity\Node|null
   *   NULL if a node could not be loaded.
   */
  protected function loadBillNode(string $print_num): ?Node {
    try {
      $nodes = $this->entityTypeManager
        ->getStorage('node')
        ->loadByProperties(['title' => $print_num]);
      /**
       * @var \Drupal\node\Entity\Node|null $ret
       */
      $ret = (count($nodes) == 1) ? current($nodes) : NULL;
    }
    catch (\Throwable) {
      $ret = NULL;
    }
    return $ret;
  }

  /**
   * Loads a taxonomy term by id.  Returns NULL if not found or on exception.
   */
  protected function loadTerm(int $id): ?Term {
    try {
      /**
       * @var \Drupal\taxonomy\Entity\Term $ret
       */
      $ret = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->load($id);
    }
    catch (\Throwable) {
      $ret = NULL;
    }
    return $ret;
  }

  /**
   * Compiles the array of events for a series of match results.
   *
   * @param array $results
   *   An array of MatchResults objects.
   *
   * @return array
   *   An array of event arrays, appropriate for saving to a queue item.
   *   Events will be sorted first by priority (descending), then source
   *   timestamp (descending).  The first item should always be the
   *   "primary" event.
   */
  protected function compileEvents(array $results): array {
    $events = [];
    /**
     * @var \Drupal\nys_bill_notifications\MatchResults $one_result
     */
    foreach ($results as $one_result) {
      /**
       * @var \Drupal\nys_bill_notifications\BillTestBase $one_test
       */
      foreach ($one_result->getMatches() as $one_test) {
        $events[] = [
          'name' => $one_test->getName(),
          'timestamp' => $one_result->getSourceTime(),
          'text' => $one_test->getSummary($one_result->getUpdate()),
          'context' => $one_test->context($one_result->getUpdate()),
          'priority' => $one_test->getPriority(),
        ];
      }
    }

    // Sort the events by priority (descending) and timestamp (descending).
    usort(
          $events, function (array $a, array $b) {
            if ($a['priority'] != $b['priority']) {
                return $a['priority'] < $b['priority'] ? 1 : -1;
            }
            elseif ($a['timestamp'] != $b['timestamp']) {
                return $a['timestamp'] < $b['timestamp'] ? 1 : -1;
            }
            else {
                return 0;
            }
          }
      );

    return $events;
  }

  /**
   * Creates queue entries for bill notification events.
   *
   * $results is an array of MatchResults objects.  All events in all results
   * are compiled.  The bill, term, compiled events are used to create a queue
   * item template, which is inserted once for each "chunk" of subscribers.
   *
   * @param array $results
   *   An array of MatchResults objects (all tested updates for the same bill).
   * @param \Drupal\node\Entity\Node $bill
   *   The bill receiving the updates.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term representing the lineage root of the bill.
   *
   * @throws \Exception
   */
  public function queueEvents(array $results, Node $bill, Term $term): void {
    // Ensure the primary_event is populated.
    $events = $this->compileEvents($results);
    $primary_event = array_shift($events);
    if (!$primary_event) {
      return;
    }

    // Build a template for the queue items.
    $bill_nid = $bill->id();
    $root_tid = $term->id();
    $print_num = $bill->getTitle();
    $queue_template = [
      'print_num' => $print_num,
      'target_id' => $root_tid,
      'target_type' => 'taxonomy_term',
      'bill_nid' => $bill_nid,
      'events' => $events,
      'primary_event' => $primary_event,
      'subject' => $this->config->get('subject') ?? SubscriptionQueue::DEFAULT_SUBJECT,
    ];

    // Get all subscribers.  Each chunk is one queue item.
    $subs = Subscription::getSubscribers('taxonomy_term', $root_tid);
    foreach ($subs as $user_chunk) {
      if (count($user_chunk)) {
        $queue_template['recipients'] = $user_chunk;
        $this->queue->createItem($queue_template);
      }
    }
  }

  /**
   * Processes all bill updates from a time range, creating queue entries.
   *
   * Timestamp ranges for Openleg are exclusive to inclusive: (from, to]
   *
   * @param mixed $time_from
   *   A timestamp, preferably epoch time or OpenLeg standard format.
   *   This value is exclusive.
   * @param mixed $time_to
   *   A timestamp, preferably epoch time or OpenLeg standard format.
   *   This value is inclusive.
   * @param array $params
   *   An array of options to pass into the OpenLeg API.
   *
   * @return array
   *   An array of all match results, keyed by bill print number.
   *
   * @throws \Exception
   */
  public function process(mixed $time_from = 0, mixed $time_to = 0, array $params = []): array {
    // Get the updates based on the requested time range.
    $updates = $this->retrieveUpdates($time_from, $time_to, $params);

    // Get the test results.
    $matches = $this->testUpdates($updates->result()->items);

    // Process the results.
    $this->processResults($matches);

    return $matches;
  }

  /**
   * Processes an array of MatchResults to create queue items.
   *
   * @param array $matches
   *   An array, keyed by bill print number, with each element being an array
   *   of MatchResults (one results per update block).  Each MatchResult may
   *   have multiple matching tests.
   *
   * @throws \Exception
   */
  public function processResults(array $matches): void {
    // For each bill, there could be multiple updates, each with their own test
    // results.  Each result is a collection of tests which matched the update.
    foreach ($matches as $print_num => $results) {
      // If a bill cannot be loaded, report and skip this update.
      if (!$bill = \Drupal::service('nys_bill.bills_helper')->loadBillByTitle($print_num)) {
        $this->logger->error(
              'Could not load bill @print_num while processing updates',
              ['@print_num' => $print_num]
          );
        continue;
      }

      // If the taxonomy term cannot be loaded, report and skip this update.
      $root_tid = $bill->field_bill_multi_session_root->target_id ?? 0;
      if (!($term = $this->loadTerm($root_tid))) {
        $this->logger->error(
              'Could not load term @tax_id for @print_num while processing updates',
              ['@print_num' => $print_num, '@tax_id' => $root_tid]
          );
        continue;
      }

      $this->queueEvents($results, $bill, $term);
    }

  }

}
