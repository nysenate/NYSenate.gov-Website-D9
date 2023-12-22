<?php

namespace Drupal\nys_sage\Commands;

use Drupal\nys_sage\Logger\SageLogger;
use Drush\Commands\DrushCommands;

/**
 * Drush command class for nys_sage.
 */
class SageCommands extends DrushCommands {

  /**
   * NYS Sage's Logger service.
   *
   * @var \Drupal\nys_sage\Logger\SageLogger
   */
  protected SageLogger $sageLogger;

  /**
   * Constructor.
   */
  public function __construct(SageLogger $sageLogger) {
    parent::__construct();
    $this->sageLogger = $sageLogger;
  }

  /**
   * Import objects from OpenLeg.
   *
   * @option integer $max-age
   *   Overrides the module's configured max retention setting; number of days.
   * @usage drush nys_sage:cron
   *   Expires SAGE log entries using configured maximum retention time.
   * @usage drush sage-cron --max-age=5
   *   Expires SAGE log entries older than 5 days.
   *
   * @command nys_sage:cron
   * @aliases sage-cron
   */
  public function sageLogCron(array $options = ['max-age' => NULL]): int {
    $max_age = $options['max-age'] ?? NULL;
    if (is_null($max_age)) {
      $this->sageLogger->cron();
    }
    else {
      $this->sageLogger->expireEntries($max_age * $this->sageLogger::SAGE_ONE_DAY);
    }
    return DRUSH_SUCCESS;
  }

}
