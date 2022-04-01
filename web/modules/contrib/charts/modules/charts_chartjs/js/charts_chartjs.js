/**
 * @file
 * JavaScript integration between Chart.js and Drupal.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.chartsChartjs = {
    attach: function (context, settings) {

      $('.charts-chartjs').each(function (param) {
        // Store attributes before switching div for canvas element.
        var chartId = $(this).attr('id');
        var dataChart = "data-chart='" + document.getElementById(chartId).getAttribute("data-chart") + "'";
        var style = 'style="' + document.getElementById(chartId).getAttribute('style') + '"';

        $(this).replaceWith(function (n) {
          return '<canvas ' + dataChart + style + 'id="' + chartId + '"' + '>' + n + '</canvas>'
        });

        $('#' + chartId).once().each(function () {
          var chartjsChart = $(this).attr('data-chart');
          var chart = JSON.parse(chartjsChart);


          // For gauge charts
          if (chart['type'] === 'linearGauge') {
            var scale = [];
            scale['range'] = chart['range'];
            scale['scaleColorRanges'] = chart['scale_color_ranges'];
          }

          var options;
          if (chart['type'] === 'linearGauge') {
            options = {scale: scale}
          }
          else {
            options = chart['options']
          }

          var myChart = new Chart(chartId, {
            type: chart['type'],
            data: chart['data'],
            options: options
          });

        });

      });

    }
  };

}(jQuery));
