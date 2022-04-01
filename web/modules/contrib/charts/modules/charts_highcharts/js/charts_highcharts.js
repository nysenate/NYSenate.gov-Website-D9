/**
 * @file
 * JavaScript integration between Highcharts and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsHighcharts = {
    attach: function (context, settings) {

      $('.charts-highchart').once().each(function () {
        if ($(this).attr('data-chart')) {
          var highcharts = $(this).attr('data-chart');
          var hc = JSON.parse(highcharts);
          if (hc.chart.type === 'pie') {
            delete hc.plotOptions.bar;
            hc.plotOptions.pie = {
              allowPointSelect: true,
              cursor: 'pointer',
              showInLegend: true,
              dataLabels: {
                enabled: hc.plotOptions.pie.dataLabels.enabled,
                format: '{point.y:,.0f}'
              },
              depth: hc.plotOptions.pie.depth
            };
            hc.legend.enabled = true;
            hc.legend.labelFormatter = function () {
              var legendIndex = this.index;
              return this.series.chart.axes[0].categories[legendIndex];
            };

            hc.tooltip.formatter = function () {
              var sliceIndex = this.point.index;
              var sliceName = this.series.chart.axes[0].categories[sliceIndex];
              var sliceSuffix = this.series.tooltipOptions.valueSuffix;
              return '' + sliceName +
                  ' : ' + this.y + sliceSuffix;
            };

          }
          // Allow Highcharts to use the default formatter for y-axis labels.
          var suffix = hc.yAxis[0].labels.suffix;
          var prefix = hc.yAxis[0].labels.prefix;
          hc.yAxis[0].labels = {
            formatter: function () {
              return prefix + this.axis.defaultLabelFormatter.call(this) + suffix;
            }
          };
          // If there's a secondary y-axis, format its label as well.
          if (hc.yAxis[1]) {
            var suffix1 = hc.yAxis[1].labels.suffix;
            var prefix1 = hc.yAxis[1].labels.prefix;
            hc.yAxis[1].labels = {
              formatter: function () {
                return prefix1 + this.axis.defaultLabelFormatter.call(this) + suffix1;
              }
            };
            if (hc.series[1]) {
              hc.series[1].yAxis = 1;
            }
          }

          $(this).highcharts(hc);
        }
      });
    }
  };
}(jQuery));
