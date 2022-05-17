<?php

/**
 * @file
 * Drush integration for the config_split module.
 */

use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function config_split_drush_command() {
  $items['config-split-export'] = [
    'description' => 'Export only split configuration to a directory.',
    'core' => ['8+'],
    'aliases' => ['csex'],
    'arguments' => [
      'split' => 'The split configuration to export, if none is given do a normal export.',
    ],
    'options' => [],
    'examples' => [
      'drush config-split-export development' => 'Export development configuration; assumes a "development" split, export only that.',
    ],
  ];

  $items['config-split-import'] = [
    'description' => 'Import only config from a split.',
    'core' => ['8+'],
    'aliases' => ['csim'],
    'arguments' => [
      'split' => 'The split configuration to export, if none is given do a normal import.',
    ],
    'options' => [],
    'examples' => [
      'drush config-split-import' => 'Import configuration as drush cim does.',
    ],
  ];

  return $items;
}

/**
 * Command callback: Export config to specified directory (usually sync).
 */
function drush_config_split_export($split = NULL) {
  try {
    // Make the magic happen.
    \Drupal::service('config_split.cli')->ioExport($split, new ConfigSplitDrush8Io(), 'dt');
  }
  catch (Exception $e) {
    return drush_set_error('DRUSH_CONFIG_ERROR', $e->getMessage());
  }
}

/**
 * Command callback. Import from specified config directory (defaults to sync).
 */
function drush_config_split_import($split = NULL) {
  try {
    // Make the magic happen.
    \Drupal::service('config_split.cli')->ioImport($split, new ConfigSplitDrush8Io(), 'dt');
  }
  catch (Exception $e) {
    return drush_set_error('DRUSH_CONFIG_ERROR', $e->getMessage());
  }
}

// @codingStandardsIgnoreStart
/**
 * Class ConfigSplitDrush8Io.
 *
 * This is a stand in for \Symfony\Component\Console\Style\StyleInterface with
 * drush 8 so that we don't need to depend on symfony components.
 */
class ConfigSplitDrush8Io {

  public function confirm($text) {
    return drush_confirm($text);
  }

  public function success($text) {
    drush_log($text, LogLevel::SUCCESS);
  }

  public function warning($text) {
    drush_log($text, LogLevel::WARNING);
  }

  public function error($text) {
    drush_log($text, LogLevel::ERROR);
  }

  public function text($text) {
    drush_log($text, LogLevel::NOTICE);
  }
}
// @codingStandardsIgnoreEnd
