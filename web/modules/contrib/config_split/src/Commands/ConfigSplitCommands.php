<?php

namespace Drupal\config_split\Commands;

use Drupal\config_split\ConfigSplitCliService;
use Drush\Commands\DrushCommands;

/**
 * Class ConfigSplitCommands.
 *
 * This is the Drush 9 and 10 commands.
 *
 * @package Drupal\config_split\Commands
 */
class ConfigSplitCommands extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\config_split\ConfigSplitCliService
   */
  protected $cliService;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\config_split\ConfigSplitCliService $cliService
   *   The CLI service which allows interoperability.
   */
  public function __construct(ConfigSplitCliService $cliService) {
    $this->cliService = $cliService;
  }

  /**
   * Export only split configuration to a directory.
   *
   * @param string $split
   *   The split configuration to export, if none is given do a normal export.
   *
   * @command config-split:export
   *
   * @usage drush config-split:export development
   *   Export development configuration; assumes a "development" split export
   *   only that.
   *
   * @aliases csex
   */
  public function splitExport($split = NULL) {
    $this->cliService->ioExport($split, $this->io(), 'dt');
  }

  /**
   * Import only config from a split.
   *
   * @param string $split
   *   The split configuration to export, if none is given do a normal import.
   *
   * @command config-split:import
   *
   * @usage drush config-split:import development
   *   Import development configuration; assumes a "development" split import
   *   only that.
   *
   * @aliases csim
   */
  public function splitImport($split = NULL) {
    $this->cliService->ioImport($split, $this->io(), 'dt');
  }

}
