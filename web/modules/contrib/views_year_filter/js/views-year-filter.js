/**
 * @file
 * Js file to handle filter state.
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.views_year_filter = {
    attach: function (context, settings) {
      const operatorInput = $('[name="options[operator]"]');
      const supportedOperators = ['=', '!=', '<', '<=', '>', '>=', 'between'];
      // In the normal dates operator is select.
      if (operatorInput.is('select')) {
        operatorInput.on('change', function () {
          handleDateInputState($(this).val());
        });
      }
      else {
        // In timestamp dates like created and changed operator is radio inputs.
        operatorInput.on('click', function () {
          if ($(this).is(':checked')) {
            handleDateInputState($(this).val());
          }
          $(this).on('change', function () {
            handleDateInputState($(this).val());
          });
        });
      }

      /**
       * Handle Date input/Select.
       *
       * @param val
       */
      function handleDateInputState(val) {
        const datYearInput = $('[value="date_year"]');
        const datInput = $('[value="date"]');
        if (!supportedOperators.includes(val)) {
          datYearInput.attr('disabled', true);
          datYearInput.prop('checked', false);
          datInput.prop("checked", true);
        }
        else {
          datYearInput.attr('disabled', false);
        }
      }
    }
  };
})(jQuery, Drupal);
