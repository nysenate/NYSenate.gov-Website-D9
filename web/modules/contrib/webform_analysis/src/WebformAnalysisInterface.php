<?php

namespace Drupal\webform_analysis;

/**
 * Defines an interface for webform analysis classes.
 */
interface WebformAnalysisInterface {

  /**
   * Get Webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   Webform object.
   */
  public function getWebform();

  /**
   * Set components and save webform.
   *
   * @param array $components
   *   The components name.
   */
  public function setComponents(array $components = []);

  /**
   * Get Components.
   *
   * @return array
   *   Components.
   */
  public function getComponents();

  /**
   * Set Chart Type.
   *
   * @param string $chart_type
   *   Set chart type and save webform.
   */
  public function setChartType($chart_type = '');

  /**
   * Get Chart Type.
   *
   * @return array
   *   Chart type.
   */
  public function getChartType();

  /**
   * Get Elements.
   *
   * @return array
   *   Element.
   */
  public function getElements();

  /**
   * Get Component Values Count.
   *
   * @param string $component
   *   The component name.
   *
   * @return array
   *   Values.
   */
  public function getComponentValuesCount($component);

  /**
   * Get Component Rows.
   *
   * @param string $component
   *   The component name.
   * @param array $header
   *   The first line data.
   * @param bool $value_label_with_count
   *   If true, add count to label.
   *
   * @return array
   *   Rows.
   */
  public function getComponentRows($component, array $header = [], $value_label_with_count = FALSE);

  /**
   * Get Component title.
   *
   * @param string $component
   *   The component name.
   *
   * @return string
   *   Component title.
   */
  public function getComponentTitle($component);

  /**
   * Is Int.
   *
   * @param string $i
   *   String Number.
   *
   * @return bool
   *   Is Int.
   */
  public function isInt($i = '');

  /**
   * Cast Numeric.
   *
   * @param string $i
   *   String Number.
   *
   * @return string
   *   Cast Numeric.
   */
  public function castNumeric($i = '');

  /**
   * Get Chart Type Options.
   *
   * @return array
   *   Chart Type Options.
   */
  public static function getChartTypeOptions();

}
