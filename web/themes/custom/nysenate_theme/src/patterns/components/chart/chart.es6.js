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
      $(once('chartInit', '.chart', context))
        .each(function (index) {
          const $chart = $(this).find('div');
          const colors = $chart.data('colors');
          const values = $chart.data('values');
          const newId = `chart${index > 0 ? `-${index}` : ''}`;
          $chart.attr('id', newId);
          const id = $chart.attr('id');

          let data = [];
          if (values) {
            data = values.map((v, i) => {
              const value = parseInt(v);
              const y =
                typeof value === 'number' ? (value >= 0 ? value : 0) : 0;

              if (i === 0) {
                return {
                  y,
                  sliced: true,
                  selected: true
                };
              }
              return { y };
            });
          }

          // eslint-disable-next-line no-undef
          Highcharts.chart(id, {
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
