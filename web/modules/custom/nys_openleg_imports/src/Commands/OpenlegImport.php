<?php

namespace Drupal\nys_openleg_imports\Commands;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\State\State;
use Drupal\nys_openleg_api\ConditionalLogger;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\EmptyList;
use Drupal\nys_openleg_api\Plugin\OpenlegApi\Response\YearBasedSearchList;
use Drupal\nys_openleg_api\Request;
use Drupal\nys_openleg_api\Service\Api;
use Drupal\nys_openleg_imports\ImporterInterface;
use Drupal\nys_openleg_imports\ImportResult;
use Drupal\nys_openleg_imports\Service\OpenlegImporterManager;
use Drupal\nys_slack\Service\Slack;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;

/**
 * Drush command class for nys_openleg_imports.
 */
class OpenlegImport extends DrushCommands {

  /**
   * Default options for import command.
   *
   * @var array
   */
  protected array $defaultOptions = [
    'mode' => 'updates',
    'to' => NULL,
    'from' => NULL,
    'ids' => NULL,
    'session' => NULL,
    'limit' => 0,
    'offset' => 1,
    'force' => FALSE,
    'log-level' => RfcLogLevel::NOTICE,
  ];

  /**
   * The nys_openleg_imports Manager service.
   *
   * @var \Drupal\nys_openleg_imports\Service\OpenlegImporterManager
   */
  protected OpenlegImporterManager $manager;

  /**
   * The nys_openleg Manager service.
   *
   * @var \Drupal\nys_openleg_api\Service\Api
   */
  protected Api $api;

  /**
   * Drupal's State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected State $state;

  /**
   * The resource type to be imported.  Must match an existing importer plugin.
   *
   * @var string
   */
  protected string $type = '';

  /**
   * The Drush command-line options, after sanity checks and defaults.
   *
   * @var array
   */
  protected array $options;

  /**
   * Slack service.
   *
   * @var \Drupal\nys_slack\Service\Slack
   */
  protected Slack $slack;

  /**
   * An importer to be used with this execution.
   *
   * @var \Drupal\nys_openleg_imports\ImporterInterface
   */
  protected ImporterInterface $importer;

  /**
   * Custom logger channel for OpenLeg imports.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $openlegLogger;

  /**
   * Constructor.
   */
  public function __construct(Api $api, OpenlegImporterManager $manager, State $state, Slack $slack, LoggerChannelInterface $logChannel) {
    parent::__construct();

    $this->manager = $manager;
    $this->api = $api;
    $this->state = $state;
    $this->openlegLogger = $logChannel;
    $this->slack = $slack;
  }

  /**
   * Gets a type-specific value from the State service.
   */
  protected function getState(string $name, $default = NULL) {
    if ($this->type) {
      $full_name = implode('.', ['nys_openleg_imports', $this->type, $name]);
      return $this->state->get($full_name, $default);
    }
    else {
      return $default;
    }
  }

  /**
   * Sets a type-specific state value.
   */
  protected function setState(string $name, $value): void {
    if ($this->type) {
      $full_name = implode('.', ['nys_openleg_imports', $this->type, $name]);
      $this->state->set($full_name, $value);
    }
  }

  /**
   * Sets default values for options which depend on run-time state.
   */
  protected function populateDefaults(): void {
    $this->defaultOptions['from'] = $this->getState('last_run.updates', 0);
    $this->defaultOptions['to'] = time();
  }

  /**
   * Replaces NULL values with the corresponding default value.
   */
  protected function resolveOptions(array $options): array {
    $this->populateDefaults();

    // Any options with a NULL value need a default.
    foreach ($this->defaultOptions as $name => $value) {
      if (is_null($options[$name] ?? NULL)) {
        $options[$name] = $value;
      }
    }

    // This should be using our conditional logger.  If not, ignore it.
    try {
      if ($this->openlegLogger instanceof ConditionalLogger) {
        $this->openlegLogger->setLogLevel($options['log-level']);
      }
    }
    catch (\Throwable) {
    }

    // The --ids option can be specified multiple times (array), and each item
    // can be a comma-delimited list.  Collapse it all into a flat array.
    $list = [];
    foreach (($options['ids'] ?? []) as $val) {
      $list = array_merge($list, explode(',', $val));
    }
    $options['ids'] = $list;

    return $options;
  }

  /**
   * Processes the lock state, considering the command line options.
   *
   * @return bool
   *   Returns FALSE if a lock exists and no bypass is directed.
   */
  protected function processLock(): bool {
    $has_lock = $this->getState('locked', 0);
    if ($has_lock) {
      $this->openlegLogger->warning("Process lock detected ...");
      if (!$this->options['force']) {
        $message = "Process lock in place since " .
          date(Request::OPENLEG_TIME_SIMPLE, $has_lock) .
          ".  Wait for it to release, or use option --force to reset.";
        $this->openlegLogger->critical($message);
        $opts = array_intersect_key($this->options,
          [
            'ids' => 0,
            'to' => 0,
            'from' => 0,
            'session' => 0,
            'limit' => 0,
            'offset' => 0,
          ]
        );
        $attach = implode(' ', array_filter(array_map('trim', explode("\n", var_export($opts, 1)))));
        $this->slack->init()
          ->addAttachment('type: ' . $this->type)
          ->addAttachment('options: ' . $attach)
          ->send('Unexpected process lock detected during OpenLeg imports');
        return FALSE;
      }
      $this->openlegLogger->notice("Ignoring lock because --force was used.");
    }
    return TRUE;
  }

  /**
   * Sets up the command's environment.
   *
   * @param string $type
   *   The type (plugin id) of importer to use.
   * @param array $options
   *   The command-line options.
   *
   * @return bool
   *   Indicates if setup was successful.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   */
  protected function doSetup(string $type, array $options = []): bool {
    // Get the importer and note the start of execution.
    $this->importer = $this->getImporter($type);
    $this->openlegLogger->info(
      "[@time] Beginning @type import", [
        '@type' => $type,
        '@time' => date(Request::OPENLEG_TIME_SIMPLE, time()),
      ]
    );

    // Populate all options.
    $this->type = $type;
    $this->options = $this->resolveOptions($options);

    // Process the lock.
    if (!$this->processLock()) {
      return FALSE;
    }

    // Set the lock and return.
    $this->setState('locked', time());
    $this->setState('last_run', time());
    return TRUE;
  }

  /**
   * Clean-up tasks after import is complete.
   */
  protected function doCleanup(ImportResult $result): void {
    // Record the results.
    $result->report($this->openlegLogger);

    // Release the lock.
    $this->setState('locked', 0);
  }

  /**
   * Wrapper to execute an import command.
   *
   * Note that the return is a shell success/fail in the context of drush.
   * Non-zero returns indicate an error condition.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   */
  protected function execute(string $type, array $options, string $callback): int {
    // Set up or die.
    if (!$this->doSetup($type, $options)) {
      return 1;
    }

    // Call the handler and send the result through clean up.
    $this->doCleanup($this->$callback($options));

    return 0;
  }

  /**
   * Import objects from OpenLeg based on a timeframe of updates.
   *
   * @param string $type
   *   The type of resource to import.  Must match an existing importer plugin.
   * @param array $options
   *   An associative array of command-line options.
   *
   * @option from
   *   A timestamp (in any format recognized by strtotime()) indicating the
   *   beginning of the update window.  Defaults to the last run saved state.
   * @option to
   *   A timestamp (in any format recognized by strtotime()) indicating the
   *   end of the update window.  Defaults to current time.
   * @option force
   *   Ignore the process lock, if it has been set.
   * @usage drush nys_openleg_import:import-updates bills
   *   Imports bills based on update blocks issued since the last run, up to
   *   right now.
   * @usage drush nys_openleg_import:import-updates bills --from=2020-02-01
   *   --to=2020-02-04 Imports bills based on update blocks issued between Feb.
   *   1, 2020 and Feb. 4, 2020, inclusive.
   *
   * @command nys_openleg_imports:import-updates
   * @aliases nysol-import-updates, nysol-iu
   *
   * @throws \Drush\Exceptions\CommandFailedException
   */
  public function importUpdates(
    string $type,
    array $options = ['from' => NULL, 'to' => NULL, 'force' => FALSE],
  ): int {
    return $this->execute($type, $options, 'doImportUpdate');
  }

  /**
   * Handler/callback to import updates.
   *
   * @param array $options
   *   The original command-line options.
   *
   * @see importUpdates()
   */
  protected function doImportUpdate(array $options): ImportResult {
    // Only set the last_run.updates marker if no `to` time was specified.
    if (!($options['to'] ?? 0)) {
      $this->setState('last_run.updates', time());
    }

    // Run the update import and return the result.
    return $this->importer->importUpdates($this->options['from'], $this->options['to']);
  }

  /**
   * Import objects from OpenLeg.
   *
   * @param string $type
   *   The type of resource to import.  Must match an existing importer plugin.
   * @param array $options
   *   An associative array of command-line options.
   *
   * @option ids
   *   A list of unique resource IDs, e.g., 2020-S123, or '2020/3,2020/4'.
   * @option session
   *   A legislative session or calendar year to import.  Options limit and
   *   offset can be used to process batches.
   * @option limit
   *   Limits the number of items to be imported. (for session)
   * @option offset
   *   Starts importing at the specified record.  (for session)
   * @option force
   *   Ignores the process lock, if it has been set.
   * @option log-level
   *   The level of messages to be logged.  See RfcLogLevel constants.
   * @usage drush nys_openleg_import:import bills --ids=2020-S123,2020-S456
   *   Imports two bills: 2020-S123 and 2020-S456
   * @usage drush nysol-i agendas --ids=2020/3,2020/4,2020/5
   *   Imports three agendas: 2020/3, 2020/4, 2020/5
   * @usage drush nysol-i calendars --session=2021 --limit=10
   *   Imports the first 10 calendars for the 2021 session.
   * @usage drush nysol-i calendars --session=2021 --limit=10 --offset=11
   *   Imports the second set of 10 calendars for the 2021 session.
   *
   * @command nys_openleg_imports:import
   * @aliases nysol-import,nysol-i
   *
   * @throws \Drush\Exceptions\CommandFailedException
   */
  public function import(
    string $type,
    array $options = [
      'ids' => [],
      'session' => 0,
      'limit' => 0,
      'offset' => 1,
      'force' => FALSE,
    ],
  ): int {
    return $this->execute($type, $options, 'doImport');
  }

  /**
   * Handler/callback for a standard import.
   *
   * @param array $options
   *   The original command-line options.
   *
   * @see import()
   */
  protected function doImport(array $options): ImportResult {
    // If a session is specified, search for the items.
    if ($this->options['session']) {
      $this->addSessionIds();
    }

    // Run the import.
    $this->openlegLogger->info('Total IDs selected: @count', ['@count' => count($this->options['ids'])]);
    return $this->importer->import($this->options['ids']);
  }

  /**
   * Adds all items for a session to the list of IDs to be imported.
   */
  protected function addSessionIds(): void {
    // Set params and search.
    $params = [
      'limit' => $this->options['limit'],
      'offset' => $this->options['offset'],
    ];
    $search = $this->api->get($this->importer->getRequesterName(), $this->options['session'], $params);

    // If a ResponseSearch is returned, add the results.
    if ($search instanceof YearBasedSearchList) {
      $names = $search->getIdFromYearList();
      $this->openlegLogger->notice('Adding @session items from session @year', [
        '@session' => count($names),
        '@year' => $this->options['session'],
      ]);
    }
    // If an EmptyList is returned, nothing was found.
    elseif ($search instanceof EmptyList) {
      $names = [];
      $this->openlegLogger->notice('No items found for session @year', ['@year' => $this->options['session']]);
    }
    // Anything else is unexpected.  Tell somebody.
    else {
      $names = [];
      $this->openlegLogger->warning('Unexpected return from session search (success: @success, @msg)', [
        '@msg' => $search->message(),
        '@success' => $search->success(),
      ]);
    }

    // Add any IDs found during the search.
    $this->options['ids'] = array_filter(
      array_unique(
        array_merge($this->options['ids'], $names)
      )
    );
  }

  /**
   * Creates/caches the importer to be used.
   *
   * @param string $type
   *   The type (plugin id) of importer to create.
   *
   * @return \Drupal\nys_openleg_imports\ImporterInterface
   *   The importer for the specified $type.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   *   If the importer plugin could not be instantiated.
   */
  protected function getImporter(string $type): ImporterInterface {
    // If the import plugin is not found, report and quit.
    try {
      /** @var \Drupal\nys_openleg_imports\ImporterBase $importer */
      $importer = $this->manager->getImporter($type);
    }
    catch (\Throwable $e) {
      $this->openlegLogger->error('Could not instantiate importer for @type', [
        '@type' => $type,
        '@msg' => $e->getMessage(),
      ]);
      throw new CommandFailedException("Could not instantiate importer for '$type'.");
    }
    return $importer;
  }

}
