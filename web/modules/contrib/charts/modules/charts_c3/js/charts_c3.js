/**
 * @file
 * JavaScript integration between C3 and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsC3 = {
    attach: function (context, settings) {

      $('.charts-c3').each(function (param) {
        var chartId = $(this).attr('id');
        $('#' + chartId).once().each(function () {
          if ($(this).attr('data-chart')) {
            var c3Chart = $(this).attr('data-chart');
            c3.generate(JSON.parse(c3Chart));
          }
        });
      });
    }
  };
}(jQuery));
