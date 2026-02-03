/**
 * @file
 * Limit the year datepicker to current year or earlier.
 */

(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.nysOpenDataYearFilterLimit = {
    attach: function (context, settings) {
      var $input = $(".js-datepicker-years-filter", context);
      if (!$input.length) {
        return;
      }

      var maxYear = new Date().getFullYear();

      // Disable future year cells when datepicker is shown
      $input.on("show.bs.datepicker", function () {
        disableFutureYears(maxYear);
      });
    },
  };

  /**
   * Disable and style future year cells in the datepicker.
   */
  function disableFutureYears(maxYear) {
    $(".datepicker-years .year").each(function () {
      var year = parseInt($(this).text().trim(), 10);
      if (year > maxYear) {
        $(this)
          .addClass("disabled")
          .css({ pointerEvents: "none", opacity: "0.5" });
      }
    });
  }
})(jQuery, Drupal);
