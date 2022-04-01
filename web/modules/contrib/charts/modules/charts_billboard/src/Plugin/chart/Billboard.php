<?php

namespace Drupal\charts_billboard\Plugin\chart;

use Drupal\charts\Plugin\chart\AbstractChart;
use Drupal\charts_billboard\Settings\Billboard\ChartGauge;
use Drupal\charts_billboard\Settings\Billboard\ChartPoints;
use Drupal\charts_billboard\Settings\Billboard\ChartType;
use Drupal\charts_billboard\Settings\Billboard\BillboardChart;
use Drupal\charts_billboard\Settings\Billboard\ChartTitle;
use Drupal\charts_billboard\Settings\Billboard\ChartData;
use Drupal\charts_billboard\Settings\Billboard\ChartColor;
use Drupal\charts_billboard\Settings\Billboard\ChartAxis;
use Drupal\charts_billboard\Settings\Billboard\ChartLegend;

/**
 * Define a concrete class for a Chart.
 *
 * @Chart(
 *   id = "billboard",
 *   name = @Translation("Billboard.js")
 * )
 */
class Billboard extends AbstractChart {

  /**
   * Process the chart type.
   *
   * @param array $options
   *   The chart options, which includes the type.
   *
   * @return string $type
   *   The chart type.
   */
  public function processChartType($options) {

    if ($options['type'] == 'column') {
      $type = 'bar';
    }
    else {
      $type = $options['type'];
    }
    // If Polar is checked, then convert to Radar chart type.
    if (isset($options['polar']) && $options['polar'] == 1) {
      $type = 'radar';
    }

    return $type;
  }

  /**
   * Creates a JSON Object formatted for Billboard.js Charts JavaScript to use.
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
  public function buildVariables(array $options, $chartId, array &$variables, array $categories = [], array $seriesData = [], array $attachmentDisplayOptions = [], array $customOptions = []) {

    // Create new instance of CThree.
    $bb = new BillboardChart();

    $seriesCount = count($seriesData);
    $attachmentCount = count($attachmentDisplayOptions);
    $noAttachmentDisplays = $attachmentCount === 0;
    $types = [];

    // Set the chart type.
    $type = $this->processChartType($options);
    $bbChart = new ChartType();
    $bbChart->setType($type);

    // Set the chart title.
    $bbChartTitle = new ChartTitle();
    $bbChartTitle->setText($options['title']);
    $bb->setTitle($bbChartTitle);

    // Set up the chart data object.
    $chartData = new ChartData();
    $chartData->setType($type);
    $bb->setData($chartData);
    $chartAxis = new ChartAxis();

    /**
     * For pie and donut chart types, depending on the number of data fields,
     * the charts will either use data fields or label fields for the
     * categories. If only one data field is selected, then the label field
     * will serve as the categories. If multiple data fields are selected,
     * they will become the categories.
     */
    if ($type == 'pie' || $type == 'donut') {
      // Set the charts colors.
      $chartColor = new ChartColor();
      $seriesColors = [];
      if ($seriesCount > 1) {
        $bbData = [];
        for ($i = 0; $i < $seriesCount; $i++) {
          $bbDataTemp = $seriesData[$i]['data'];
          array_unshift($bbDataTemp, $seriesData[$i]['name']);
          array_push($bbData, $bbDataTemp);
          $seriesColor = $seriesData[$i]['color'];
          array_push($seriesColors, $seriesColor);
        }
      }
      else {
        $bbData = [];
        for ($i = 0; $i < count($seriesData[0]['data']); $i++) {
          $bbDataTemp = $seriesData[0]['data'][$i];
          $bbSeriesDataTemp = array_merge([$categories[$i]], [$bbDataTemp]);
          array_push($bbData, $bbSeriesDataTemp);
        }
      }
      $chartData->setColumns($bbData);
      $chartColor->setPattern($seriesColors);
      $bb->setColor($chartColor);
    }
    else {
      // Set the charts colors.
      $chartColor = new ChartColor();
      $seriesColors = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        $seriesColor = $seriesData[$i]['color'];
        array_push($seriesColors, $seriesColor);
      }
      $chartColor->setPattern($seriesColors);
      $bb->setColor($chartColor);

      // Set up the chart data object.
      $bbData = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        $bbDataTemp = $seriesData[$i]['data'];
        array_unshift($bbDataTemp, $seriesData[$i]['name']);
        array_push($bbData, $bbDataTemp);
      }
      // Billboard does not use bar, so column must be used.
      if ($options['type'] == 'bar') {
        $chartAxis->setRotated(TRUE);
      }
      elseif ($options['type'] == 'column') {
        $chartData->setType('bar');
        $chartAxis->setRotated(FALSE);
      }
      if ($options['type'] == 'scatter') {
        /**
         * Old code: $chartAxis->setX(['tick' => ['fit' => FALSE]]);
         * New code: uses the Scatter Field in charts_fields. Still needs
         * plenty of work.
         */
        $fieldLabel = $seriesData[0]['name'];
        array_shift($bbData[0]);
        $scatterFieldData = $bbData[0];
        $scatterFieldX = [];
        $scatterFieldY = [];
        for ($i = 0; $i < count($scatterFieldData); $i++) {
          array_push($scatterFieldX, $scatterFieldData[$i][0]);
          array_push($scatterFieldY, $scatterFieldData[$i][1]);
        }
        array_unshift($scatterFieldX, $fieldLabel . '_x');
        array_unshift($scatterFieldY, $fieldLabel);
        $bbData[0] = $scatterFieldX;
        $bbData[1] = $scatterFieldY;
        $chartAxis->setX(['tick' => ['fit' => FALSE]]);
        $xs = new \stdClass();
        $xs->{$fieldLabel} = $fieldLabel . '_x';
        $chartData->setXs($xs);
        $chartData->setX('');
      }
      else {
        array_unshift($categories, 'x');
        array_push($bbData, $categories);
      }

      $chartData->setColumns($bbData);
      $bb->setAxis($chartAxis);
    }

    // Set the chart types.
    for ($i = 0; $i < count($seriesData); $i++) {
      $type = $this->processChartType($seriesData[$i]);
      if (isset($options['polar']) && $options['polar'] == 1) {
        $type = 'radar';
      }
      $types[$seriesData[$i]['name']] = $type;
    }
    $chartData->types = $types;

    // Set labels to FALSE if disabled in form.
    if (empty($options['data_labels'])) {
      $chartData->setLabels(FALSE);
    }

    // Sets the primary y axis.
    $showAxis['show'] = TRUE;
    $showAxis['label'] = $options['yaxis_title'];
    $chartAxis->y = $showAxis;

    // Sets secondary axis from the first attachment only.
    if (!$noAttachmentDisplays && $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $showSecAxis['show'] = TRUE;
      $showSecAxis['label'] = $attachmentDisplayOptions[0]['style']['options']['yaxis_title'];
      $chartAxis->y2 = $showSecAxis;
    }

    // Determines if chart is stacked.
    if (!empty($options['grouping'] && $options['grouping'] == TRUE)) {
      $seriesNames = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        array_push($seriesNames, $seriesData[$i]['name']);
      }
      $chartData->setGroups([$seriesNames]);
    }

    // Set gauge options.
    if ($type == 'gauge') {
      $gauge = new ChartGauge();
      $gauge->setMin((int) $options['min']);
      $gauge->setMax((int) $options['max']);
      $gauge->setUnits($options['yaxis_suffix']);
      $bb->setGauge($gauge);
    }

    // Set markers (points)
    if (($type == 'line') || ($type == 'spline')) {
      $points = new ChartPoints();
      if ($options['data_markers'] == 'FALSE') {
        $points->setShow(FALSE);
      }
      else {
        $points->setShow(TRUE);
      }
      $bb->setPoint($points);
    }

    // Set legend.
    $legend = new ChartLegend();
    if (empty($options['legend_position'])) {
      $legend->setShow(FALSE);
    }
    else {
      $legend->setShow(TRUE);
    }
    $bb->setLegend($legend);

    $bindTo = '#' . $chartId;
    $bb->setBindTo($bindTo);

    /**
     * Override Billboard classes. These will only override what is in
     * charts_billboard/src/Settings/CThree/CThree.php
     * but you can use more of the Billboard API, as you are not constrained
     * to what is in this class. See:
     * charts_billboard/src/Plugin/override/BillboardOverrides.php
     */
    foreach ($customOptions as $option => $key) {
      $setter = 'set' . ucfirst($option);
      if (method_exists($bb, $setter)) {
        $bb->$setter($customOptions[$option]);
      }
    }

    $variables['chart_type'] = 'billboard';
    $variables['content_attributes']['data-chart'][] = json_encode($bb);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-bb';
  }

}
