<?php

namespace Drupal\nys_openleg_imports\Commands;

use Drupal\Core\State\State;
use Drupal\nys_openleg\Api\Request;
use Drupal\nys_openleg\Service\ApiManager;
use Drupal\nys_openleg_imports\Service\OpenlegImporterManager;
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
   * @var \Drupal\nys_openleg\Service\ApiManager
   */
  protected ApiManager $api;

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
   * Constructor.
   */
  public function __construct(ApiManager $api, OpenlegImporterManager $manager, State $state) {
    parent::__construct();
    $this->manager = $manager;
    $this->api = $api;
    $this->state = $state;
    $this->populateDefaults();
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
  protected function setState(string $name, $value) {
    if ($this->type) {
      $full_name = implode('.', ['nys_openleg_imports', $this->type, $name]);
      $this->state->set($full_name, $value);
    }
  }

  /**
   * Sets default values for options which depend on run-time state.
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

    // The --ids option can be specified multiple times (array), and each item
    // can be a comma-delimited list.  Collapse it all into a flat array.
    $list = [];
    foreach (($options['ids'] ?? []) as $val) {
      $list = array_merge($list, explode(',', $val));
    }

    // Change any hyphens to slashes.
    $options['ids'] = array_map(
          function ($v) {
              return str_replace('-', '/', $v);
          },
          array_filter(array_unique($list))
      );

    return $options;
  }

  /**
   * Processes the lock state, considering the command line options.
   *
   * @return bool
   *   Returns FALSE if a lock exists and no bypass is directed.
   */
  protected function processLock() : bool {
    $has_lock = $this->getState('locked', 0);
    if ($has_lock) {
      $this->logger()->warning("Process lock detected ...");
      if (!$this->options['force']) {
        $message = "Process lock in place since " .
                date(Request::OPENLEG_TIME_SIMPLE, $has_lock) .
                ".  Wait for it to release, or use option --force to reset.";
        $this->logger()->critical($message);
        return FALSE;
      }
      $this->logger()->info("Ignoring lock because --force was used.");
    }
    return TRUE;
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
  public function importUpdates(string $type, array $options = [
    'from' => NULL,
    'to' => NULL,
    'force' => FALSE,
  ]): int {

    // If the import plugin is not found, report and quit.
    try {
      /**
       * @var \Drupal\nys_openleg_imports\ImporterBase $importer
       */
      $importer = $this->manager->getImporter($type);
    }
    catch (\Throwable $e) {
      throw new CommandFailedException("Could not instantiate importer for '$type'.");
    }

    // Populate all options.
    $this->type = $type;
    // Ensure the 'from' option is populated from 'last_run.updates'.  The
    // default behavior uses 'last_run'.
    $options['from'] = $options['from'] ?? $this->getState('last_run.updates', 0);
    $this->options = $this->resolveOptions($options);

    // Process the lock.
    if (!$this->processLock()) {
      return DRUSH_FRAMEWORK_ERROR;
    }
    $this->setState('locked', time());

    $result = $importer->importUpdates($this->options['from'], $this->options['to']);
    $result->report($this->logger());

    // Release the lock.  Set the last_run marker if no 'to' time was passed.
    $this->setState('locked', 0);
    if (!($options['to'] ?? 0)) {
      $this->setState('last_run.updates', time());
    }

    return DRUSH_SUCCESS;

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
  public function import(string $type, array $options = [
    'ids' => [],
    'session' => 0,
    'limit' => 0,
    'offset' => 1,
    'force' => FALSE,
  ]): int {

    // If the import plugin is not found, report and quit.
    try {
      /**
       * @var \Drupal\nys_openleg_imports\ImporterBase $importer
       */
      $importer = $this->manager->getImporter($type);
    }
    catch (\Throwable $e) {
      throw new CommandFailedException("Could not instantiate importer for '$type'.");
    }

    // Populate all options.
    $this->type = $type;
    $this->options = $this->resolveOptions($options);

    // Check for a lock, and set the lock.
    if (!$this->processLock()) {
      return DRUSH_FRAMEWORK_ERROR;
    }

    // @todo Locks per type may not be needed anymore.  One global lock?
    $this->setState('locked', time());
    $this->setState('last_run', time());

    // If a session is specified, search for the items.
    if ($this->options['session']) {
      $params = [
        'limit' => $this->options['limit'],
        'offset' => $this->options['offset'],
      ];
      /**
       * @var \Drupal\nys_openleg\Plugin\OpenlegApi\Response\ResponseSearch $search
       */
      $search = $this->api->get($importer->getRequester(), $this->options['session'], $params);
      $names = $importer->getIdFromYearList($search);
      $this->options['ids'] = array_filter(
            array_unique(
                array_merge($this->options['ids'], $names)
            )
        );
    }

    // Get the list of items being imported.  The 'ids' option takes precedence.
    $result = $importer->import($this->options['ids']);
    $result->report($this->logger());

    // Release the lock.
    $this->setState('locked', 0);

    return DRUSH_SUCCESS;
  }

}
