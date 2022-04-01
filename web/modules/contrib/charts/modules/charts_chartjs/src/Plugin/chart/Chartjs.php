<?php

namespace Drupal\charts_chartjs\Plugin\chart;

use Drupal\charts\Plugin\chart\AbstractChart;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsChart;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsData;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsOptions;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsScales;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsStacking;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsTickConfigurationOptions;
use Drupal\charts_chartjs\Settings\Chartjs\ChartjsTicks;

/**
 * Define a concrete class for a Chart.
 *
 * @Chart(
 *   id = "chartjs",
 *   name = @Translation("Chart.js")
 * )
 */
class Chartjs extends AbstractChart {

  /**
   * Outputs a type that can be used by Chart.js.
   *
   * @param array $options
   *   Options.
   *
   * @return string $type
   *   Type.
   *
   */
  protected function buildChartType($options) {
    switch ($options['type']) {
      case 'bar':
        $type = 'horizontalBar';
        break;

      case 'column':
        $type = 'bar';
        break;

      case 'spline':
        $type = 'line';
        break;

      case 'donut':
        $type = 'doughnut';
        break;

      case 'area':
        $type = 'line';
        break;

      case 'gauge':
        $type = 'linearGauge';
        break;

      default:
        $type = $options['type'];
        break;

    }
    if (isset($options['polar']) && $options['polar'] == 1) {
      $type = 'radar';
    }
    // May need to handle attachment type.

    // @todo: need to handle gauge (https://github.com/scottmcculloch/Chart.LinearGauge.js) and scatter.

    return $type;
  }

  /**
   * Build the scale color ranges.
   *
   * @param array $options
   *   Options.
   *
   * @return array $scaleColorRanges
   *   Scale color ranges.
   */
  protected function buildGaugeOptions(array $options) {
    $scaleColorRanges = [];
    $scaleColorRanges[0] = new \stdClass();
    $scaleColorRanges[1] = new \stdClass();
    $scaleColorRanges[2] = new \stdClass();
    // Red.
    $scaleColorRanges[0]->start = isset($options['red_from']) ? $options['red_from'] : '';
    $scaleColorRanges[0]->end = isset($options['red_to']) ? $options['red_to'] : '';
    $scaleColorRanges[0]->color = '#ff000c';
    // Yellow.
    $scaleColorRanges[1]->start = isset($options['yellow_from']) ? $options['yellow_from'] : '';
    $scaleColorRanges[1]->end = isset($options['yellow_to']) ? $options['yellow_to'] : '';
    $scaleColorRanges[1]->color = '#ffff00';
    // Green.
    $scaleColorRanges[2]->start = isset($options['green_from']) ? $options['green_from'] : '';
    $scaleColorRanges[2]->end = isset($options['green_to']) ? $options['green_to'] : '';
    $scaleColorRanges[2]->color = '#008000';

    return $scaleColorRanges;
  }

  /**
   * Build the options.
   *
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_chartjs\Settings\Chartjs\ChartjsOptions
   */
  protected function buildOptions(array $options) {
    $chartjsOptions = new ChartjsOptions();
    $chartjsScales = new ChartjsScales();
    $chartjsStacking = new ChartjsStacking();
    $ticks = new ChartjsTicks();
    $tickOptions = new ChartjsTickConfigurationOptions();

    // Determines if chart is stacked.
    if (!empty($options['grouping']) && $options['grouping'] == TRUE) {
      $grouping = TRUE;
    }
    else {
      $grouping = FALSE;
    }
    $chartjsStacking->setStacking($grouping);
    $chartjsScales->setXAxes([$chartjsStacking]);
    $ticks->setTicks($tickOptions);
    $ticks->setStacked($grouping);
    $chartjsScales->setYAxes([$ticks]);
    $chartjsOptions->setScales($chartjsScales);
    $tooltip = new \stdClass();
    if($options['tooltips'] == 'TRUE') {
      $tooltip->enabled = TRUE;
    } else {
      $tooltip->enabled = FALSE;
    }
    $chartjsOptions->setTooltips($tooltip);
    // Set Legend.
    $chartjsOptions->setLegend($this->buildLegend($options));
    // Set Title.
    $chartjsOptions->setTitle($this->buildTitle($options));

    return $chartjsOptions;
  }

  /**
   * @param $options
   *
   * @return \stdClass
   */
  protected function buildLegend($options) {
    $legend = new \stdClass();

    if(isset($options['legend']) && !empty($options['legend_position']) && ($options['legend'] == TRUE)) {
      $legend->display = TRUE;
      $legend->position = $options['legend_position'];
    }
    else {
      $legend->display = FALSE;
    }

    return $legend;
  }

  /**
   * @param $options
   *
   * @return \stdClass
   */
  protected function buildTitle($options) {
    $title = new \stdClass();

    if(!empty($options['title_position']) && !empty($options['title'])) {
      $title->display = TRUE;
      $title->position = $options['title_position'];
      $title->text = $options['title'];
    }
    else {
      $title->display = FALSE;
    }

    return $title;
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

    // Create new instance of Chart.js.
    $chartjs = new ChartjsChart();
    $chartjsData = new ChartjsData();

    // Useful variables for loops.
    $seriesCount = count($seriesData);
    $attachmentCount = count($attachmentDisplayOptions);
    $noAttachmentDisplays = $attachmentCount === 0;

    // Set the chart type.
    $chartjs->setType($this->buildChartType($options));

    // Set the chart labels.
    $chartjsData->setLabels($categories);

    // Populate the data object.
    $dataset = [];
    for ($i = 0; $i < $seriesCount; $i++) {
      $dataset[$i] = new \stdClass();
      $dataset[$i]->label = $seriesData[$i]['name'];
      $dataset[$i]->data = $seriesData[$i]['data'];
      $dataset[$i]->backgroundColor = $seriesData[$i]['color'];
      // Type is needed here for mixed charts.
      $dataset[$i]->type = $this->buildChartType($seriesData[$i]);
      if ($seriesData[$i]['type'] == 'area') {
        $dataset[$i]->fill = 'origin';
      }
      else {
        $dataset[$i]->fill = FALSE;
      }
      if (!empty($options['polar']) && $options['polar'] == 1) {
        $dataset[$i]->borderColor = $seriesData[$i]['color'];
        $dataset[$i]->type = 'radar';
      }
      if ($dataset[$i]->type == 'linearGauge') {
        $dataset[$i]->offset = ($i + 1) * 10;
      }
      if ($dataset[$i]->type == 'scatter') {
        $data = $dataset[$i]->data;
        $scatterDataSet = [];
        for ($i = 0; $i < count($data); $i++) {
          $scatterData = new \stdClass();
          $scatterData->x = $data[$i][0];
          $scatterData->y = $data[$i][1];
          array_push($scatterDataSet, $scatterData);
        }
        $dataset[0]->data = $scatterDataSet;
      }
    }
    $chartjsData->setDatasets($dataset);
    $chartjs->setData($chartjsData);

    // Set Gauge settings
    if ($options['type'] == 'gauge') {
      $chartjs->setScaleColorRanges($this->buildGaugeOptions($options));
      $range = [];
      // $range->startValue = $options['min'].
      // $range->endValue = $options['max'].
      $range['startValue'] = 1;
      $range['endValue'] = 1000;
      $chartjs->setRange($range);
    }

    $chartjs->setOptions($this->buildOptions($options));

    /*
     * Override Chart.js classes. These will only override what is in
     * charts_chartjs/src/Settings/Chartjs/ChartjsChart.php
     * but you can use more of the Chart.js API, as you are not constrained
     * to what is in this class. See:
     * charts_chartjs/src/Plugin/override/ChartjsOverrides.php
     */
    foreach($customOptions as $option => $key) {
      $setter = 'set' . ucfirst($option);
      if (method_exists($chartjs, $setter)) {
        $chartjs->$setter($customOptions[$option]);
      }
    }

    $variables['chart_type'] = 'chartjs';
    $variables['content_attributes']['data-chart'][] = json_encode($chartjs);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-chartjs';
  }

}
