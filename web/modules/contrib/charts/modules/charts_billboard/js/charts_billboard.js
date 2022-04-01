/**
 * @file
 * JavaScript integration between Billboard and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsBb = {
    attach: function (context, settings) {

      $('.charts-bb').each(function (param) {
        var chartId = $(this).attr('id');
        $('#' + chartId).once().each(function () {
          if ($(this).attr('data-chart')) {
            var bbChart = $(this).attr('data-chart');
            bb.generate(JSON.parse(bbChart));
          }
        });
      });
    }
  };
}(jQuery));
