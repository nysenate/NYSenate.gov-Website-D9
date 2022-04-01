<?php

namespace Drupal\search_api_page\Config;

interface ViewModeConfigInterface {

  /**
   * Gets the configured view mode for a given data source and bundle.
   *
   * @param string $dataSourceId
   *   The data source id.
   * @param string $bundleId
   *   The bundle id.
   *
   * @return string
   *   The view mode machine name.
   */
  public function getViewMode($dataSourceId, $bundleId);

  /**
   * Gets the default view mode for a given data source.
   *
   * @param string $dataSourceId
   *   The data source id.
   *
   * @return string
   *   The view mode machine name.
   */
  public function getDefaultViewMode($dataSourceId);

  /**
   * Determines if the given data source has any view mode overrides.
   *
   * @param string $dataSourceId
   *   The data source id.
   *
   * @return bool
   *   True if any overrides are present, false if there are not.
   */
  public function hasOverrides($dataSourceId);

  /**
   * Determines if a view mode is overridden for a given data source and bundle.
   *
   * @param string $dataSourceId
   *   The data source id.
   * @param string $bundleId
   *   The bundle id.
   *
   * @return bool
   *   True if the bundle is overridden, false if it is not.
   */
  public function isOverridden($dataSourceId, $bundleId);

}
