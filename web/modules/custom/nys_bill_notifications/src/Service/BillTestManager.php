<?php

namespace Drupal\nys_bill_notifications\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\nys_bill_notifications\MatchResults;

/**
 * Provides an interface to consume bill test plugins.
 */
class BillTestManager extends DefaultPluginManager {

  /**
   * Preconfigured logging channel for bill notifications.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Instances of each definition.
   *
   * @var array
   */
  protected array $instances;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerChannel $logger) {
    parent::__construct(
          'Plugin/BillNotifications/BillTests',
          $namespaces,
          $module_handler,
          'Drupal\nys_bill_notifications\BillTestInterface',
          'Drupal\nys_bill_notifications\Annotation\BillTest'
      );
    $this->setCacheBackend($cache_backend, 'nys_bill_notifications.billtests');
    $this->logger = $logger;
  }

  /**
   * Creates instances of every test, sorted.
   *
   * If $sort evaluates to boolean false, the return array is sorted using only
   * the Id.  Otherwise, it is sorted by priority, then by Id.
   *
   * @return \Drupal\nys_bill_notifications\BillTestInterface[]
   *   Array of all update test plugins, sorted.
   */
  public function getActiveTests(): array {
    // Create all the instances, if necessary.
    if (!isset($this->instances)) {
      $this->instances = [];
      foreach ($this->getDefinitions() as $key => $val) {
        if (!($val['disabled'] ?? FALSE)) {
          $this->instances[$key] = $this->createInstance($key);
        }
      }
    }
    return $this->instances;
  }

  /**
   * Test an OpenLeg update blocks against all tests.
   */
  public function matchUpdate(object $update): ?MatchResults {
    // Get a new MatchResults object.  A malformed update is untestable.
    try {
      $ret = new MatchResults($update);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to test update block, malformed?', ['@id' => var_export($update, 1)]);
      return NULL;
    }
    foreach ($this->getActiveTests() as $test) {
      if ($test->isMatch($update)) {
        $ret->addMatch($test);
      }
    }
    return $ret;
  }

}
