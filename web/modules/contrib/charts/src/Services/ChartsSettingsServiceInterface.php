<?php

namespace Drupal\charts\Services;

/**
 * Charts Settings Service Interface.
 */
interface ChartsSettingsServiceInterface {

  /**
   * Get Charts Settings.
   *
   * @return array
   *   Charts settings.
   */
  public function getChartsSettings();

}
