<?php

namespace Drupal\nys_openleg_imports;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for NYS Openleg import plugins.
 */
interface ImporterInterface extends ContainerFactoryPluginInterface {

  /**
   * Imports items referenced by updates during a specified time period.
   */
  public function importUpdates(string $time_from, string $time_to): ImportResult;

  /**
   * Import one item specified by unique id/name.
   */
  public function importItem(string $name): ImportResult;

  /**
   * Imports Openleg items based on array of unique IDs.
   *
   * @param array $items
   *   An of strings corresponding to the unique names to be imported.
   *
   * @return \Drupal\nys_openleg_imports\ImportResult
   *   The results of the import.
   */
  public function import(array $items): ImportResult;

}
