/**
 * @file
 * Behaviors for the Chart.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Chart behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.chart = {
    attach: function (context) {
      $('.chart', context)
        .once('chartInit')
        .each(function () {
          const $chart = $(this).find('div');
          const colors = $chart.data('colors');
          const values = $chart.data('values');

          let data = [];
          if (values) {
            data = values.map((v, i) => {
              if (i === 0) {
                return {
                  y: v,
                  sliced: true,
                  selected: true
                };
              }
              return { y: v };
            });
          }

          // eslint-disable-next-line no-undef
          Highcharts.chart('vote-chart', {
            colors,
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
            series: [
              {
                data
              }
            ]
          });
        });
    }
  };
})(document, Drupal, jQuery);
