<?php

namespace Drupal\charts_highcharts\Plugin\chart;

use Drupal\charts\Plugin\chart\AbstractChart;
use Drupal\charts_highcharts\Settings\Highcharts\Chart;
use Drupal\charts_highcharts\Settings\Highcharts\ChartCreditsPosition;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLegendItemStyle;
use Drupal\charts_highcharts\Settings\Highcharts\ChartTitle;
use Drupal\charts_highcharts\Settings\Highcharts\ExportingOptions;
use Drupal\charts_highcharts\Settings\Highcharts\Marker;
use Drupal\charts_highcharts\Settings\Highcharts\Pane;
use Drupal\charts_highcharts\Settings\Highcharts\PlotBands;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptionsStacking;
use Drupal\charts_highcharts\Settings\Highcharts\ThreeDimensionalOptions;
use Drupal\charts_highcharts\Settings\Highcharts\Xaxis;
use Drupal\charts_highcharts\Settings\Highcharts\XaxisTitle;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLabel;
use Drupal\charts_highcharts\Settings\Highcharts\YaxisLabel;
use Drupal\charts_highcharts\Settings\Highcharts\Yaxis;
use Drupal\charts_highcharts\Settings\Highcharts\YaxisTitle;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptions;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptionsSeries;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptionsSeriesDataLabels;
use Drupal\charts_highcharts\Settings\Highcharts\Tooltip;
use Drupal\charts_highcharts\Settings\Highcharts\ChartCredits;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLegend;
use Drupal\charts_highcharts\Settings\Highcharts\HighchartsOptions;
use Drupal\Core\Config\Config;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Defines a concrete class for a Highcharts.
 *
 * @Chart(
 *   id = "highcharts",
 *   name = @Translation("Highcharts")
 * )
 */
class Highcharts extends AbstractChart {

  /**
   * Build the legend.
   *
   * @param array $options
   *   Options
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\ChartLegend
   *   Chart legend.
   */
  protected function buildChartLegend(array $options) {
    // Retrieve Highcharts-specific default settings.
    $highchartsConfig = \Drupal::config('charts_highcharts.settings')
      ->get();
    // Incorporate Highcharts-specific default settings into $options.
    $options = array_merge($options, $highchartsConfig);

    $chartLegend = new ChartLegend();
    if (!empty($options['legend_layout'])) {
      $chartLegend->setLayout($options['legend_layout']);
    }
    if (!empty($options['legend_background_color'])) {
      $chartLegend->setBackgroundColor($options['legend_background_color']);
    }
    if (!empty($options['legend_border_width'])) {
      $chartLegend->setBorderWidth($options['legend_border_width']);
    }
    if (!empty($options['legend_shadow'])) {
      $chartLegend->setShadow($options['legend_shadow']);
    }
    if (empty($options['legend_position'])) {
      $chartLegend->setEnabled(FALSE);
    }
    elseif (in_array($options['legend_position'], ['left', 'right'])) {
      if (!empty($options['legend_position'])) {
        $chartLegend->setAlign($options['legend_position']);
      }
      $chartLegend->setVerticalAlign('top');
      $chartLegend->setY(80);
      if (!empty($options['legend_position']) && $options['legend_position'] == 'left') {
        $chartLegend->setX(0);
      }
    }
    else {
      if (!empty($options['legend_position'])) {
        $chartLegend->setVerticalAlign($options['legend_position']);
      }
      $chartLegend->setAlign('center');
      $chartLegend->setX(0);
      $chartLegend->setY(0);
      $chartLegend->setFloating(FALSE);
    }
    $styles = new ChartLegendItemStyle();
    if (!empty($options['item_style_color'])) {
      $styles->setColor($options['item_style_color']);
    }
    if (!empty($options['text_overflow'])) {
      $styles->setTextOverflow($options['text_overflow']);
    }
    $chartLegend->setItemStyle($styles);
    // Get language direction and set legend direction upon
    $direction = \Drupal::languageManager()->getCurrentLanguage()->getDirection();
    $chartLegend->setDirection($direction == 'ltr' ? FALSE : TRUE);

    return $chartLegend;
  }

  /**
   * Build the plotOptions.
   *
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\PlotOptions
   *   PlotOptions.
   */
  protected function buildPlotOptions(array $options) {
    $plotOptions = new PlotOptions();
    $plotOptionsStacking = new PlotOptionsStacking();
    $plotOptionsSeries = new PlotOptionsSeries();
    $plotOptionsSeriesDataLabels = new PlotOptionsSeriesDataLabels();
    $plotOptionsSeriesDataLabels->setEnabled($options['data_labels']);
    // Set plot options if stacked chart.
    if (!empty($options['grouping'])) {
      $plotOptions->setPlotOptions($options['type'], $plotOptionsStacking);
      $plotOptionsStacking->setDataLabels($plotOptionsSeriesDataLabels);
      // Set markers if grouped.
      if (in_array($options['type'], ['area', 'line', 'scatter', 'spline'])) {
        $marker = new Marker();
        if ($options['data_markers'] == 'FALSE') {
          $marker->setEnabled(FALSE);
        }
        else {
          $marker->setEnabled(TRUE);
        }
        $plotOptionsStacking->setMarker($marker);
      }
      if ($options['type'] == 'gauge') {
        $plotOptionsStacking->setStacking('');
      }
    }
    else {
      $plotOptions->setPlotOptions($options['type'], $plotOptionsSeries);
      $plotOptionsSeries->setDataLabels($plotOptionsSeriesDataLabels);
      // Set markers if not grouped.
      if (in_array($options['type'], ['area', 'line', 'scatter', 'spline'])) {
        $marker = new Marker();
        if ($options['data_markers'] == 'FALSE') {
          $marker->setEnabled(FALSE);
        }
        else {
          $marker->setEnabled(TRUE);
        }
        $plotOptionsSeries->setMarker($marker);
      }
    }
    if (isset($options['data_labels'])) {
      $plotOptionsSeriesDataLabels->setEnabled($options['data_labels']);
    }
    // Determines if chart is three-dimensional.
    if (!empty($options['three_dimensional'])) {
      $plotOptionsSeries->setDepth(45);
    }

    return $plotOptions;
  }

  /**
   * Build the x-axis.
   *
   * @param array $options
   *   Options.
   * @param array $seriesData
   *   SeriesData.
   * @param array $categories
   *   Categories.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\Xaxis
   *   X-axis.
   */
  protected function buildXaxis(array $options, array $seriesData, array $categories) {
    $chartXaxis = new Xaxis();
    $chartLabels = new ChartLabel();
    // Set x-axis label rotation.
    if (isset($options['xaxis_labels_rotation'])) {
      $chartLabels->setRotation($options['xaxis_labels_rotation']);
    }
    $xAxisTitle = new XaxisTitle();
    if (isset($options['xaxis_title'])) {
      $xAxisTitle->setText($options['xaxis_title']);
    }
    // If donut or pie and only one data point with multiple fields in use.
    if (($options['type'] == 'pie' || $options['type'] == 'donut') && (count($seriesData[0]['data']) == 1)) {
      unset($categories);
      $categories = [];
      for ($i = 0; $i < count($seriesData); $i++) {
        array_push($categories, $seriesData[$i]['name']);
      }
    }
    if (!empty($options['reverse_series']) && $options['reverse_series'] == 1) {
      unset($categories);
      $categories = [];
      for ($i = 0; $i < count($seriesData); $i++) {
        array_push($categories, $seriesData[$i]['name']);
      }
    }
    $chartXaxis->setCategories($categories);
    if (isset($options['xaxis_tickmark_placement'])) {
      switch ($options['xaxis_tickmark_placement']) {
        case 'on':
        case 'between':
          $chartXaxis->setTickmarkPlacement($options['xaxis_tickmark_placement']);
          break;
        default:
      }
    }
    // Set x-axis title.
    $chartXaxis->setTitle($xAxisTitle);
    $chartXaxis->setLabels($chartLabels);
    // Set min.
    if (!empty($options['xaxis_min'])) {
      $chartXaxis->setMin((int) $options['xaxis_min']);
    }
    // Set max.
    if (!empty($options['xaxis_max'])) {
      $chartXaxis->setMax((int) $options['xaxis_max']);
    }
    // Set interval.
    if (!empty($options['xaxis_interval'])) {
      $chartXaxis->setTickInterval((int) $options['xaxis_interval']);
    }
    // Set line width.
    if (isset($options['xaxis_line_width']) && is_int($options['xaxis_line_width'])) {
      $chartXaxis->setLineWidth($options['xaxis_line_width']);
    }

    return $chartXaxis;
  }

  /**
   * Build the y-axis labels.
   *
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\YaxisLabel
   */
  protected function buildYaxisLabels(array $options) {
    $yaxisLabels = new YaxisLabel();
    if (!empty($options['yaxis_suffix'])) {
      $yaxisLabels->setYaxisLabelSuffix($options['yaxis_suffix']);
    }
    if (!empty($options['yaxis_prefix'])) {
      $yaxisLabels->setYaxisLabelPrefix($options['yaxis_prefix']);
    }

    return $yaxisLabels;
  }

  /**
   * Build the secondary y-axis.
   *
   * @param array $attachmentDisplayOptions
   *   Attachment display options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\Yaxis
   */
  protected function buildSecondaryYaxis(array $attachmentDisplayOptions) {
    $chartYaxisSecondary = new Yaxis();
    $yAxisTitleSecondary = new YaxisTitle();
    $yAxisTitleSecondary->setText($attachmentDisplayOptions[0]['style']['options']['yaxis_title']);
    $chartYaxisSecondary->setTitle($yAxisTitleSecondary);
    $yaxisLabelsSecondary = new YaxisLabel();
    if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_suffix'])) {
      $yaxisLabelsSecondary->setYaxisLabelSuffix($attachmentDisplayOptions[0]['style']['options']['yaxis_suffix']);
    }
    if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_prefix'])) {
      $yaxisLabelsSecondary->setYaxisLabelPrefix($attachmentDisplayOptions[0]['style']['options']['yaxis_prefix']);
    }
    $chartYaxisSecondary->setLabels($yaxisLabelsSecondary);
    $chartYaxisSecondary->opposite = 'true';
    if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_min'])) {
      $chartYaxisSecondary->setMin($attachmentDisplayOptions[0]['style']['options']['yaxis_min']);
    }
    if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_max'])) {
      $chartYaxisSecondary->setMax($attachmentDisplayOptions[0]['style']['options']['yaxis_max']);
    }

    return $chartYaxisSecondary;
  }

  /**
   * Build the y-axis.
   *
   * @param string $title
   *   Title.
   * @param mixed $yaxisLabels
   *   Labels.
   * @param array $options
   *   Options.
   * @param array $seriesData
   *   SeriesData.
   * @param array $categories
   *   Categories.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\Yaxis
   */
  protected function buildYaxis($title, $yaxisLabels, array $options, array $seriesData, array $categories) {

    $chartYaxis = new Yaxis();
    $yAxisTitle = new YaxisTitle();
    $yAxisTitle->setText($title);
    if (!empty($options['yaxis_min'])) {
      $chartYaxis->setMin($options['yaxis_min']);
    }
    if (!empty($options['yaxis_max'])) {
      $chartYaxis->setMax($options['yaxis_max']);
    }
    if (isset($options['yaxis_categories'])) {
      $chartYaxis->setCategories($options['yaxis_categories']);
    }
    if (!empty($options['yaxis_interval']) && is_numeric($options['yaxis_interval'])) {
      $chartYaxis->setTickInterval($options['yaxis_interval']);
    }
    if (isset($options['yaxis_show_first_label'])) {
      $chartYaxis->setShowFirstLabel($options['yaxis_show_first_label']);
      if ($options['yaxis_show_first_label']) {
        $chartYaxis->setStartOnTick(TRUE);
      }
    }
    if (isset($options['yaxis_show_last_label'])) {
      $chartYaxis->setShowLastLabel($options['yaxis_show_last_label']);
      // endOnTick needs to be TRUE for showLastLabel = TRUE to work.
      if ($options['yaxis_show_last_label']) {
        $chartYaxis->setEndOnTick(TRUE);
      }
    }
    if (isset($options['yaxis_tickmark_placement'])) {
      switch ($options['yaxis_tickmark_placement']) {
        case 'on':
        case 'between':
          $chartYaxis->setTickmarkPlacement($options['yaxis_tickmark_placement']);
          break;
        default:
      }
    }
    if (isset($options['yaxis_line_width']) && is_int($options['yaxis_line_width'])) {
      $chartYaxis->setLineWidth($options['yaxis_line_width']);
    }

    // Polar options.
    if (isset($options['polar'])) {
      if (isset($options['yaxis_gridline_interpolation'])) {
        switch ($options['yaxis_gridline_interpolation']) {
          case 'circle':
          case 'polygon':
            $chartYaxis->setGridLineInterpolation($options['yaxis_gridline_interpolation']);
            break;
          default:
        }
      }
    }

    // Gauge options.
    if ($options['type'] == 'gauge') {
      // Gauge will not work if grouping is set.
      $options['grouping'] = [];
      $plotBandsGreen = new PlotBands();
      $plotBandsYellow = new PlotBands();
      $plotBandsRed = new PlotBands();
      $gaugeColors = [];
      $plotBandsRed->setFrom($options['red_from']);
      $plotBandsRed->setTo($options['red_to']);
      $plotBandsRed->setColor('red');
      array_push($gaugeColors, $plotBandsRed);
      $plotBandsYellow->setFrom($options['yellow_from']);
      $plotBandsYellow->setTo($options['yellow_to']);
      $plotBandsYellow->setColor('yellow');
      array_push($gaugeColors, $plotBandsYellow);
      $plotBandsGreen->setFrom($options['green_from']);
      $plotBandsGreen->setTo($options['green_to']);
      $plotBandsGreen->setColor('green');
      array_push($gaugeColors, $plotBandsGreen);
      $chartYaxis->setPlotBands($gaugeColors);
      $chartYaxis->setMin((int) $options['min']);
      $chartYaxis->setMax((int) $options['max']);
      if (count($seriesData) > 1 || count($categories) > 1) {
        \Drupal::service('messenger')->addMessage(t('The gauge
          chart type does not work well with more than one value.'), 'warning');
      }
    }
    $chartYaxis->setLabels($yaxisLabels);
    $chartYaxis->setTitle($yAxisTitle);

    return $chartYaxis;
  }

  /**
   * Build Tooltip
   *
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\Tooltip
   */
  protected function buildToolTip(array $options) {
    $chartTooltip = new Tooltip();
    if (isset($options['tooltips'])) {
      $chartTooltip->setEnabled($options['tooltips']);
    }
    if (isset($options['tooltips_suffix'])) {
      $chartTooltip->setValueSuffix($options['tooltips_suffix']);
    }
    else {
      $chartTooltip->setValueSuffix('');
    }

    return $chartTooltip;
  }

  /**
   * Build credits.
   *
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\ChartCredits
   */
  protected function buildCredits(array $options) {
    $chartCredits = new ChartCredits();
    if (isset($options['credits'])) {
      $chartCredits->setEnabled(TRUE);
      $chartCredits->setText($options['credits']);
      $position = new ChartCreditsPosition();
      $position->setX(10);
      $position->setY(-20);
      $chartCredits->setPosition($position);
    }

    return $chartCredits;
  }

  /**
   * Build the chart title.
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\ChartTitle
   */
  protected function buildChartTitle(array $options) {
    $chartTitle = new ChartTitle();
    if (isset($options['title'])) {
      $chartTitle->setText($options['title']);
    }
    // Set title position.
    if (isset($options['title_position'])) {
      if ($options['title_position'] == 'in') {
        $chartTitle->setVerticalAlign('middle');
      }
      else {
        $chartTitle->setVerticalOffset(20);
      }
    }

    return $chartTitle;
  }

  /**
   * Build the chart type.
   * @param array $options
   *   Options.
   *
   * @return \Drupal\charts_highcharts\Settings\Highcharts\Chart
   */
  protected function buildChartType(array $options) {
    $chart = new Chart();
    $chart->setType($options['type']);

    // Set chart width.
    if (isset($options['width'])) {
      $chart->setWidth($options['width']);
    }

    // Set chart height.
    if (isset($options['height'])) {
      $chart->setHeight($options['height']);
    }

    // Set background color.
    if (isset($options['background'])) {
      $chart->setBackgroundColor($options['background']);
    }

    // Set polar plotting.
    if (isset($options['polar'])) {
      $chart->setPolar($options['polar']);
    }

    if (!empty($options['three_dimensional'])) {
      $threeDimensionOptions = new ThreeDimensionalOptions();
      $chart->setOptions3D($threeDimensionOptions);
      $threeDimensionOptions->setAlpha(55);
      $threeDimensionOptions->setViewDistance(0);
      $threeDimensionOptions->setBeta(0);
      if ($options['type'] != 'pie') {
        $threeDimensionOptions->setAlpha(15);
        $threeDimensionOptions->setViewDistance(25);
      }
    }

    return $chart;
  }

  /**
   * Creates a JSON Object formatted for Highcharts JavaScript to use.
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
    $noAttachmentDisplays = count($attachmentDisplayOptions) === 0;

    // @todo: make this so that it happens if any display uses donut.
    if ($options['type'] == 'donut') {
      $options['type'] = 'pie';
      // Remove donut from seriesData.
      foreach ($seriesData as &$value) {
        $value = str_replace('donut', 'pie', $value);
      }
      // Add innerSize to differentiate between donut and pie.
      foreach ($seriesData as &$value) {
        if ($options['type'] == 'pie') {
          $innerSize['showInLegend'] = 'true';
          $innerSize['innerSize'] = '40%';
          $chartPlacement = array_search($value, $seriesData);
          $seriesData[$chartPlacement] = array_merge($innerSize, $seriesData[$chartPlacement]);
        }
      }
    }
    $yAxes = [];
    $xAxisOptions = $this->buildXaxis($options, $seriesData, $categories);
    $yaxisLabels = $this->buildYaxisLabels($options);
    $chartYaxis = $this->buildYaxis($options['yaxis_title'], $yaxisLabels, $options, $seriesData, $categories);
    array_push($yAxes, $chartYaxis);
    // Chart libraries tend to support only one secondary axis.
    if (!$noAttachmentDisplays && $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $chartYaxisSecondary = $this->buildSecondaryYaxis($attachmentDisplayOptions);
      array_push($yAxes, $chartYaxisSecondary);
    }
    // Set plot options.
    $plotOptions = $this->buildPlotOptions($options);
    $chartCredits = $this->buildCredits($options);
    // Set charts legend.
    $chartLegend = $this->buildChartLegend($options);
    // Set exporting options.
    $exporting = new ExportingOptions();
    $highchart = new HighchartsOptions();
    $highchart->setChart($this->buildChartType($options));
    $highchart->setTitle($this->buildChartTitle($options));
    if (!empty($options['subtitle'])) {
      $subtitleText = new \stdClass();
      $subtitleText->text = $options['subtitle'];
      $highchart->setSubtitle($subtitleText);
    }
    $highchart->setAxisX($xAxisOptions);
    $highchart->setAxisY($yAxes);
    if ($options['type'] == 'gauge') {
      $pane = new Pane();
      $highchart->setPane($pane);
    }
    $highchart->setTooltip($this->buildToolTip($options));
    $highchart->setPlotOptions($plotOptions);
    $highchart->setCredits($chartCredits);
    $highchart->setLegend($chartLegend);
    // Usually just set the series with seriesData.
    if (($options['type'] == 'pie' || $options['type'] == 'donut') && (count($seriesData[0]['data']) == 1)) {
      for ($i = 0; $i < count($seriesData); $i++) {
        if (is_array($seriesData[$i]['data'][0]) && isset($seriesData[$i]['data'][0]['y'])) {
          foreach ($seriesData[$i]['data'][0] as $key => $value) {
            $seriesData[$i][$key] = $value;
          }
        }
        else {
          $seriesData[$i]['y'] = $seriesData[$i]['data'][0];
        }
        unset($seriesData[$i]['data']);
      }
      $chartData = ['data' => $seriesData];
      $highchart->setSeries([$chartData]);
    }
    elseif ($options['type'] == 'scatter') {
      // You probably want to enable and use the ScatterField in charts_fields.
      $data = $seriesData[0]['data'];
      $xAxisCategories['categories'] = [];
      for ($i = 0; $i < count($categories); $i++) {
        $seriesData[$i]['name'] = $categories[$i];
        $seriesData[$i]['data'] = [$data[$i]];
        // You may need additional colors for a scatter plot. This assumes a
        // comma-separated list of colors.
        if (!empty($options['scatter_colors'])) {
          $colors = explode(",", $options['scatter_colors']);
          $seriesData[$i]['color'] = $colors[$i];
        }
        array_push($xAxisCategories['categories'], $data[$i][0]);
      }
      $xAxisOptions = $this->buildXaxis($options, $seriesData, $xAxisCategories);
      $highchart->setAxisX($xAxisOptions);
      $highchart->setSeries($seriesData);
    }
    elseif (!empty($options['reverse_series']) && $options['reverse_series'] == 1) {
      for ($i = 0; $i < count($categories); $i++) {
        $seriesData[$i]['name'] = $categories[$i];
        for ($j = 0; $j < count($seriesData); $j++) {
          $seriesData[$i]['data'][$j] = $seriesData[$j]['data'][0];
        }
      }
      $seriesData = array_slice($seriesData, 0, count($categories));
      $highchart->setSeries($seriesData);
    }
    else {
      $highchart->setSeries($seriesData);
    }
    $highchart->setExporting($exporting);

    // Override Highchart classes. These will only override what is in
    // charts_highcharts/src/Settings/Highcharts/HighchartsOptions.php
    // but you can use more of the Highcharts API, as you are not constrained
    // to what is in this class. See:
    // charts_highcharts/src/Plugin/override/HighchartsOverrides.php
    foreach ($customOptions as $option => $key) {
      $setter = 'set' . ucfirst($option);
      if (method_exists($highchart, $setter)) {
        $highchart->$setter($customOptions[$option]);
      }
    }

    $variables['chart_type'] = 'highcharts';
    $variables['content_attributes']['data-chart'][] = json_encode($highchart);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-highchart';
  }

}
