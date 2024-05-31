/**
 * @file
 * Js file to handle filter state.
 */

(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.views_year_filter_datepicker = {
    attach: function (context, settings) {
      $(".js-datepicker-years-filter").each(function () {
        $(this).datepicker("destroy");
        $(this).datepicker({
          format: " yyyy",
          viewMode: "years",
          minViewMode: "years",
        });
      });
    },
  };
})(jQuery, Drupal);
