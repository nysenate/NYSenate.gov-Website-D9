<?php

namespace Drupal\webform_analysis;

/**
 * Webform Analysis Chart Interface.
 */
interface WebformAnalysisChartInterface {

  /**
   * Build.
   *
   * @param array $build
   *   Build.
   */
  public function build(array &$build = []);

  /**
   * Build Components Data.
   *
   * @return array
   *   Renderable.
   */
  public function buildComponentsData();

  /**
   * Build Component Data.
   *
   * @param \Drupal\webform_analysis\WebformAnalysisInterface $analysis
   *   Analysis.
   * @param string $component
   *   Component.
   * @param string $id
   *   Id.
   *
   * @return array
   *   Renderable.
   */
  public function buildComponentData(WebformAnalysisInterface $analysis, $component = '', $id = '');

  /**
   * CreateComponentId.
   *
   * @param string $component
   *   Component name.
   *
   * @return string
   *   Component Id.
   */
  public function createComponentId($component);

  /**
   * Create Chart.
   *
   * @param string $id
   *   Id.
   *
   * @return array
   *   Renderable.
   */
  public function createChart($id);

  /**
   * Get Header.
   *
   * @return array
   *   Header.
   */
  public function getHeader();

  /**
   * Build Pie Chart.
   *
   * @param \Drupal\webform_analysis\WebformAnalysisInterface $analysis
   *   Analysis.
   * @param string $component
   *   Component.
   * @param array $header
   *   Header.
   *
   * @return array
   *   Pie Chart.
   */
  public function buildPieChart(WebformAnalysisInterface $analysis, $component = '', array $header = []);

  /**
   * Build Attached Settings.
   *
   * @param array $charts
   *   Charts.
   *
   * @return array
   *   Attached Settings.
   */
  public function buildAttachedSettings(array $charts = []);

}
