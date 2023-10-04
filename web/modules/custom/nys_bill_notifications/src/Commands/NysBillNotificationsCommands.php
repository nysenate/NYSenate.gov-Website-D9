<?php

namespace Drupal\nys_bill_notifications\Commands;

use Drupal\Core\State\State;
use Drupal\nys_bill_notifications\Service\UpdatesProcessor;
use Drupal\nys_openleg_api\Request;
use Drush\Commands\DrushCommands;

/**
 * Drush command file for nys_bill_notifications.
 */
class NysBillNotificationsCommands extends DrushCommands {

  /**
   * Default option values.
   *
   * @var array
   */
  protected array $defaultOptions = [
    'from' => 0,
    'to' => 0,
    'force' => FALSE,
  ];

  /**
   * Drupal's State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected State $state;

  /**
   * NYS Bill Notifications Update Processor service.
   *
   * @var \Drupal\nys_bill_notifications\Service\UpdatesProcessor
   */
  protected UpdatesProcessor $updatesProcessor;

  /**
   * Local copy of passed drush options.
   *
   * @var array
   */
  protected array $options;

  /**
   * Constructor.
   */
  public function __construct(State $state, UpdatesProcessor $updatesProcessor) {
    parent::__construct();
    $this->state = $state;
    $this->updatesProcessor = $updatesProcessor;
    $this->populateDefaults();
  }

  /**
   * Processes OpenLeg update blocks to queue subscription emails.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @return int
   *   A Drush return code.
   *
   * @option from
   *   Timestamp indicating the beginning of the update window.
   * @option to
   *   Timestamp indicating the end of the update window.
   * @option force
   *   Ignores the process lock, if it has been set.
   * @usage nys_bill_notifications:processUpdates
   *   Processes all updates from last run to current time.
   * @usage nys_bill_notifications:processUpdates --from=2021-10-31
   *   Processes all updates from a specified time.
   * @usage nys_bill_notifications:processUpdates --from=2021-10-31
   *   --to=2021-11-01 Processes all updates from and to a specified time.
   *
   * @command nys_bill_notifications:processUpdates
   *
   * @aliases nysbn-pu
   */
  public function processUpdates(array $options = [
    'from' => 0,
    'to' => 0,
    'force' => FALSE,
  ]): int {
    $this->options = $this->resolveOptions($options);

    // Check for a lock, and set the lock.
    if ($has_lock = $this->getLock()) {
      $this->logger()->warning("Process lock detected ...");
      if (!$this->options['force']) {
        $message = "Process lock in place since " .
                date(Request::OPENLEG_TIME_SIMPLE, $has_lock) .
                ".  Wait for release, or use option --force to ignore it.";
        $this->logger()->critical($message);
        return DRUSH_FRAMEWORK_ERROR;
      }
      $this->logger()->info("Ignoring lock because --force was used.");
    }

    $ts = $this->setLock();
    $this->updatesProcessor->process($this->options['from'], $this->options['to']);
    $this->releaseLock();

    $this->setState('last_run', $ts);
    /* @phpstan-ignore-next-line */
    $this->logger()->success(dt('Update processing complete.'));
    return DRUSH_SUCCESS;
  }

  /**
   * Gets a value from the State service.
   */
  protected function getState(string $name, $default = NULL) {
    $full_name = 'nys_bill_notifications.' . $name;
    return $this->state->get($full_name, $default);
  }

  /**
   * Sets a state value.
   */
  protected function setState(string $name, $value) {
    $full_name = 'nys_bill_notifications.' . $name;
    $this->state->set($full_name, $value);
  }

  /**
   * Gets the time the process lock was set (zero, if not locked).
   */
  protected function getLock() {
    return $this->getState('locked', 0);
  }

  /**
   * Sets the process lock.
   *
   * @param int $timestamp
   *   If zero, current timestamp is used.
   *
   * @return int
   *   The epoch timestamp of the lock.
   */
  protected function setLock(int $timestamp = 0): int {
    $ts = $timestamp ?: time();
    $this->setState('locked', $ts);
    return $ts;
  }

  /**
   * Releases the process lock.
   */
  protected function releaseLock() {
    $this->setState('locked', 0);
  }

  /**
   * Sets sane defaults for 'from' and 'to' options.
   */
  protected function populateDefaults() {
    $this->defaultOptions['from'] = $this->getState('last_run', 0);
    $this->defaultOptions['to'] = time();
  }

  /**
   * Replaces NULL values with the corresponding default value.
   */
  protected function resolveOptions(array $options): array {
    // Any options with a NULL value need a default.
    foreach ($this->defaultOptions as $name => $value) {
      if (is_null($options[$name] ?? NULL)) {
        $options[$name] = $value;
      }
    }

    return $options;
  }

}
