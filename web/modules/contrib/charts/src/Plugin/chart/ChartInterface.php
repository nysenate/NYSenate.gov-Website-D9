<?php

namespace Drupal\charts\Plugin\chart;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Chart plugins.
 */
interface ChartInterface extends PluginInspectionInterface {

  /**
   * Creates a JSON Object formatted for the charting library JavaScript.
   *
   * @param array $options
   *   Options.
   * @param string $chartId
   *   Chart ID.
   * @param array $variables
   *   Variables.
   * @param array $categories
   *   Categories.
   * @param array $seriesData
   *   Series data.
   * @param array $attachmentDisplayOptions
   *   Attachment display options.
   * @param array $customOptions
   *   Overrides.
   */
  public function buildVariables(array $options, $chartId, array &$variables, array $categories, array $seriesData, array $attachmentDisplayOptions, array $customOptions = []);

  /**
   * Return the name of the chart.
   *
   * @return string
   *   Returns the name as a string.
   */
  public function getChartName();

}
