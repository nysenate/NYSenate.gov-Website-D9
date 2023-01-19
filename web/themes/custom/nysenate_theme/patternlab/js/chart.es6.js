/**
 * @file
 * Behaviors for the Chart.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Chart behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.chart = {
    attach: function attach(context) {
      $('.chart', context).once('chartInit').each(function () {
        var $chart = $(this).find('div');
        var colors = $chart.data('colors');
        var values = $chart.data('values');
        var data = [];

        if (values) {
          data = values.map(function (v, i) {
            if (i === 0) {
              return {
                y: v,
                sliced: true,
                selected: true
              };
            }

            return {
              y: v
            };
          });
        } // eslint-disable-next-line no-undef


        Highcharts.chart('vote-chart', {
          colors: colors,
          chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie',
            width: 165,
            height: 165
          },
          title: {
            text: ''
          },
          tooltip: {
            enabled: false
          },
          credits: {
            enabled: false
          },
          plotOptions: {
            pie: {
              dataLabels: {
                enabled: false
              }
            }
          },
          series: [{
            data: data
          }]
        });
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=chart.es6.js.map
