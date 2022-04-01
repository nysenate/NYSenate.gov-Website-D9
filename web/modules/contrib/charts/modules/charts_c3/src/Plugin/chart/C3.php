<?php

namespace Drupal\charts_c3\Plugin\chart;

use Drupal\charts\Plugin\chart\AbstractChart;
use Drupal\charts_c3\Settings\CThree\ChartGauge;
use Drupal\charts_c3\Settings\CThree\ChartPoints;
use Drupal\charts_c3\Settings\CThree\ChartType;
use Drupal\charts_c3\Settings\CThree\CThree;
use Drupal\charts_c3\Settings\CThree\ChartTitle;
use Drupal\charts_c3\Settings\CThree\ChartData;
use Drupal\charts_c3\Settings\CThree\ChartColor;
use Drupal\charts_c3\Settings\CThree\ChartAxis;
use Drupal\charts_c3\Settings\CThree\ChartLegend;

/**
 * Define a concrete class for a Chart.
 *
 * @Chart(
 *   id = "c3",
 *   name = @Translation("C3")
 * )
 */
class C3 extends AbstractChart {

  /**
   * @param $type
   *
   * @return string
   */
  public function processChartType($type) {

    if ($type == 'column') {
      $type = 'bar';
    }

    return $type;
  }

  /**
   * Creates a JSON Object formatted for C3 Charts JavaScript to use.
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
    $c3 = new CThree();

    $seriesCount = count($seriesData);
    $attachmentCount = count($attachmentDisplayOptions);
    $noAttachmentDisplays = $attachmentCount === 0;

    // Set the chart type.
    $c3Chart = new ChartType();
    $c3Chart->setType($options['type']);

    // Set the chart title.
    $c3ChartTitle = new ChartTitle();
    $c3ChartTitle->setText($options['title']);
    $c3->setTitle($c3ChartTitle);

    // Set up the chart data object.
    $chartData = new ChartData();
    $chartData->setType($options['type']);
    $c3->setData($chartData);
    $chartAxis = new ChartAxis();

    /**
     * For pie and donut chart types, depending on the number of data fields,
     * the charts will either use data fields or label fields for the
     * categories. If only one data field is selected, then the label field
     * will serve as the categories. If multiple data fields are selected,
     * they will become the categories.
     * */
    if ($options['type'] == 'pie' || $options['type'] == 'donut') {
      // Set the charts colors.
      $chartColor = new ChartColor();
      $seriesColors = [];
      if ($seriesCount > 1) {
        $c3Data = [];
        for ($i = 0; $i < $seriesCount; $i++) {
          $c3DataTemp = $seriesData[$i]['data'];
          array_unshift($c3DataTemp, $seriesData[$i]['name']);
          array_push($c3Data, $c3DataTemp);
          $seriesColor = $seriesData[$i]['color'];
          array_push($seriesColors, $seriesColor);
        }
      }
      else {
        $c3Data = [];
        for ($i = 0; $i < count($seriesData[0]['data']); $i++) {
          $c3DataTemp = $seriesData[0]['data'][$i];
          $c3SeriesDataTemp = array_merge([$categories[$i]], [$c3DataTemp]);
          array_push($c3Data, $c3SeriesDataTemp);
        }
      }
      $chartData->setColumns($c3Data);
      $chartColor->setPattern($seriesColors);
      $c3->setColor($chartColor);
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
      $c3->setColor($chartColor);

      // Set up the chart data object.
      $c3Data = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        $c3DataTemp = $seriesData[$i]['data'];
        array_unshift($c3DataTemp, $seriesData[$i]['name']);
        array_push($c3Data, $c3DataTemp);
      }
      // C3 does not use bar, so column must be used.
      if ($options['type'] == 'bar') {
        $chartAxis->setRotated(TRUE);
      }
      elseif ($options['type'] == 'column') {
        $chartData->setType('bar');
        $chartAxis->setRotated(FALSE);
      }
      if ($options['type'] == 'scatter') {
        // Old code: $chartAxis->setX(['tick' => ['fit' => FALSE]]);

        // New code: uses the Scatter Field in charts_fields. Still needs
        // plenty of work.
        $fieldLabel = $seriesData[0]['name'];
        array_shift($c3Data[0]);
        $scatterFieldData = $c3Data[0];
        $scatterFieldX = [];
        $scatterFieldY = [];
        for($i = 0; $i < count($scatterFieldData); $i++) {
          array_push($scatterFieldX, $scatterFieldData[$i][0]);
          array_push($scatterFieldY, $scatterFieldData[$i][1]);
        }
        array_unshift($scatterFieldX, $fieldLabel . '_x');
        array_unshift($scatterFieldY, $fieldLabel);
        $c3Data[0] = $scatterFieldX;
        $c3Data[1] = $scatterFieldY;
        $chartAxis->setX(['tick' => ['fit' => FALSE]]);
        $xs = new \stdClass();
        $xs->{$fieldLabel} = $fieldLabel . '_x';
        $chartData->setXs($xs);
        $chartData->setX('');
      }
      else {
        array_unshift($categories, 'x');
        array_push($c3Data, $categories);
      }

      $chartData->setColumns($c3Data);
      $c3->setAxis($chartAxis);
    }

    // Set the chart types.
    $types = [];
    for ($i = 0; $i < $seriesCount; $i++) {
      $types[$seriesData[$i]['name']] = $this->processChartType($seriesData[$i]['type']);
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
    if (!$noAttachmentDisplays &&
      isset($attachmentDisplayOptions[0]['inherit_yaxis']) &&
      $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $showSecAxis['show'] = TRUE;
      $showSecAxis['label'] = $attachmentDisplayOptions[0]['style']['options']['yaxis_title'];
      $chartAxis->y2 = $showSecAxis;
    }

    // Determines if chart is stacked.
    if (isset($options['grouping']) && $options['grouping'] === TRUE) {
      $seriesNames = [];
      for ($i = 0; $i < $seriesCount; $i++) {
        array_push($seriesNames, $seriesData[$i]['name']);
      }
      $chartData->setGroups([$seriesNames]);
    }

    // Set gauge options.
    if ($options['type'] == 'gauge') {
      $gauge = new ChartGauge();
      $gauge->setMin((int) $options['min']);
      $gauge->setMax((int) $options['max']);
      $gauge->setUnits($options['yaxis_suffix']);
      $c3->setGauge($gauge);
    }

    // Set markers (points)
    if (($options['type'] == 'line') || ($options['type'] == 'spline')) {
      $points = new ChartPoints();
      if ($options['data_markers'] == 'FALSE') {
        $points->setShow(FALSE);
      }
      else {
        $points->setShow(TRUE);
      }
      $c3->setPoint($points);
    }

    // Set legend
    $legend = new ChartLegend();
    if (empty($options['legend_position'])) {
      $legend->setShow(FALSE);
    } else {
      $legend->setShow(TRUE);
    }
    $c3->setLegend($legend);

    $bindTo = '#' . $chartId;
    $c3->setBindTo($bindTo);

    // Override C3 classes. These will only override what is in
    // charts_c3/src/Settings/CThree/CThree.php
    // but you can use more of the C3 API, as you are not constrained
    // to what is in this class. See:
    // charts_c3/src/Plugin/override/C3Overrides.php
    foreach($customOptions as $option => $key) {
      $setter = 'set' . ucfirst($option);
      if (method_exists($c3, $setter)) {
        $c3->$setter($customOptions[$option]);
      }
    }

    $variables['chart_type'] = 'c3';
    $variables['content_attributes']['data-chart'][] = json_encode($c3);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-c3';
  }

}
