<?php

namespace Drupal\charts\Services;

/**
 * Interface ChartServiceInterface.
 *
 * @package Drupal\charts\Services
 */
interface ChartServiceInterface {

  /**
   * Gets the currently selected Library.
   *
   * @return string
   *   Library selected.
   */
  public function getLibrarySelected();

  /**
   * Sets the previously set Library with the newly selected library value.
   *
   * @param string $librarySelected
   *   Library selected.
   */
  public function setLibrarySelected($librarySelected = '');

}
