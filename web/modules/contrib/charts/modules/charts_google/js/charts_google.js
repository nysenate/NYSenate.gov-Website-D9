/**
 * @file
 * JavaScript integration between Google and Drupal.
 */

(function ($) {
  'use strict';

  Drupal.googleCharts = Drupal.googleCharts || {charts: []};

  /**
   * Behavior to initialize Google Charts.
   *
   * @type {{attach: Drupal.behaviors.chartsGooglecharts.attach}}
   */
  Drupal.behaviors.chartsGooglecharts = {
    attach: function (context, settings) {
      // Load Google Charts API.
      google.charts.load('current', {packages: ['corechart', 'gauge']});

      $(context).find('body').once('load-google-charts').each(function () {
        // Re-draw charts if viewport size has been changed.
        $(window).on('resize', function () {
          Drupal.googleCharts.waitForFinalEvent(function () {
            // Re-draw Google Charts.
            Drupal.googleCharts.drawCharts(true);
          }, 200, 'reload-google-charts');
        });
      });

      // Draw Google Charts.
      Drupal.googleCharts.drawCharts();
    }
  };

  /**
   * Helper function to draw Google Charts.
   *
   * @param {boolean} reload - Reload.
   */
  Drupal.googleCharts.drawCharts = function (reload) {
    $('.charts-google').each(function () {
      var chartId = $(this).attr('id');
      var $charts;

      if (reload === true) {
        $charts = $('#' + chartId);
      }
      else {
        $charts = $('#' + chartId).once('draw-google-charts');
      }

      $charts.each(function () {
        var $chart = $(this);

        if ($chart.attr('data-chart')) {
          var data = $chart.attr('data-chart');
          var options = $chart.attr('google-options');
          var type = $chart.attr('google-chart-type');

          google.charts.setOnLoadCallback(Drupal.googleCharts.drawChart(chartId, type, data, options));
        }
      });
    });
  };

  /**
   * Helper function to draw a Google Chart.
   *
   * @param {string} chartId - Chart Id.
   * @param {string} chartType - Chart Type.
   * @param {string} dataTable - Data.
   * @param {string} googleChartOptions - Options.
   *
   * @return {function} Draw Chart.
   */
  Drupal.googleCharts.drawChart = function (chartId, chartType, dataTable, googleChartOptions) {
    return function () {
      var data = google.visualization.arrayToDataTable(JSON.parse(dataTable));
      var options = JSON.parse(googleChartOptions);

      var googleChartTypeObject = JSON.parse(chartType);
      var googleChartTypeFormatted = googleChartTypeObject.type;
      var chart;

      switch (googleChartTypeFormatted) {
        case 'BarChart':
          chart = new google.visualization.BarChart(document.getElementById(chartId));
          break;
        case 'ColumnChart':
          chart = new google.visualization.ColumnChart(document.getElementById(chartId));
          break;
        case 'DonutChart':
          chart = new google.visualization.PieChart(document.getElementById(chartId));
          break;
        case 'PieChart':
          chart = new google.visualization.PieChart(document.getElementById(chartId));
          break;
        case 'ScatterChart':
          chart = new google.visualization.ScatterChart(document.getElementById(chartId));
          break;
        case 'AreaChart':
          chart = new google.visualization.AreaChart(document.getElementById(chartId));
          break;
        case 'LineChart':
          chart = new google.visualization.LineChart(document.getElementById(chartId));
          break;
        case 'SplineChart':
          chart = new google.visualization.LineChart(document.getElementById(chartId));
          break;
        case 'GaugeChart':
          chart = new google.visualization.Gauge(document.getElementById(chartId));
          break;
        case 'GeoChart':
          chart = new google.visualization.GeoChart(document.getElementById(chartId));
      }
      // Fix for https://www.drupal.org/project/charts/issues/2950654.
      // Would be interested in a different approach that allowed the default
      // colors to be applied first, rather than unsetting.
      if (options['colors'].length > 10) {
        for (var i in options) {
          if (i === 'colors') {
            delete options[i];
            break;
          }
        }
      }

      // Rewrite the colorAxis item to include the colors: key
      if (typeof options['colorAxis'] != 'undefined') {
        var num_colors = options['colorAxis'].length;
        var colors = options['colorAxis'];
        options['colorAxis'] = options['colorAxis'].splice(num_colors);
        options['colorAxis'] = {colors: colors};
      }

      chart.draw(data, options);
    };
  };

  /**
   * Helper function to run a callback function once when triggering an event
   * multiple times.
   *
   * Example usage:
   * @code
   *  $(window).resize(function () {
   *    Drupal.googleCharts.waitForFinalEvent(function(){
   *      alert('Resize...');
   *    }, 500, "some unique string");
   *  });
   * @endcode
   */
  Drupal.googleCharts.waitForFinalEvent = (function () {
    var timers = {};
    return function (callback, ms, uniqueId) {
      if (!uniqueId) {
        uniqueId = "Don't call this twice without a uniqueId";
      }
      if (timers[uniqueId]) {
        clearTimeout(timers[uniqueId]);
      }
      timers[uniqueId] = setTimeout(callback, ms);
    };
  })();

}(jQuery));
