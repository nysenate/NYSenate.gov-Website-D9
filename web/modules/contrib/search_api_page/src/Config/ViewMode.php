<?php

namespace Drupal\search_api_page\Config;

/**
 * Value object for view mode configuration.
 */
class ViewMode implements ViewModeConfigInterface {

  const DEFAULT_VIEW_MODE = 'default';

  /**
   * @var array
   */
  private $rawConfig;

  /**
   * ViewMode constructor.
   *
   * @param array $rawConfig
   *   The raw configuration array from the configuration file.
   */
  public function __construct(array $rawConfig) {
    $this->rawConfig = $rawConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewMode($dataSourceId, $bundleId) {
    if (!isset($this->rawConfig[$dataSourceId])) {
      return self::DEFAULT_VIEW_MODE;
    }

    if (!$this->hasOverrides($dataSourceId)) {
      return $this->getDefaultViewMode($dataSourceId);
    }

    if (!$this->isOverridden($dataSourceId, $bundleId)) {
      return $this->getDefaultViewMode($dataSourceId);
    }

    return $this->rawConfig[$dataSourceId]['overrides'][$bundleId];
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultViewMode($dataSourceId) {
    if (!isset($this->rawConfig[$dataSourceId]['default'])) {
      return self::DEFAULT_VIEW_MODE;
    }

    if (empty($this->rawConfig[$dataSourceId]['default'])) {
      return self::DEFAULT_VIEW_MODE;
    }
    return $this->rawConfig[$dataSourceId]['default'];
  }

  /**
   * {@inheritDoc}
   */
  public function hasOverrides($dataSourceId) {
    if (!isset($this->rawConfig[$dataSourceId]['overrides'])) {
      return FALSE;
    }

    if (empty($this->rawConfig[$dataSourceId]['overrides'])) {
      return FALSE;
    }

    if (empty(array_filter($this->rawConfig[$dataSourceId]['overrides']))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isOverridden($dataSourceId, $bundleId) {
    if (!$this->hasOverrides($dataSourceId)) {
      return FALSE;
    }

    if (!isset($this->rawConfig[$dataSourceId]['overrides'][$bundleId])) {
      return FALSE;
    }

    if (empty($this->rawConfig[$dataSourceId]['overrides'][$bundleId])) {
      return FALSE;
    }

    return TRUE;
  }

}
