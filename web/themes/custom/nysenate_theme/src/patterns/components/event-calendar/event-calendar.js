/**
 * @file
 * Behaviors for the Event Calendar.
 */
!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Event Calendar behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.eventCalendar = {
    attach: function () {
      // Setup contextual variables.
      const isWeekView = document.querySelector('.view-display-id-page_3');
      const isMonthView = document.querySelector('.view-display-id-page_2');
      const dateFormat = !isMonthView ? 'Y-m-d' : 'Y-m';
      // Setup jQuery variables (Zebra_DatePicker has jQuery dependency).
      const $datePicker = $('#datepicker input');
      const $datePickerWrapper = $('#datepicker #container');
      // Setup DOM pointers.
      const dateInput = document.querySelector('.calendar-events-form input.bef-datepicker');
      const formSubmit = document.querySelector('.calendar-events-form input.form-submit');
      // Initiate Zebra_DatePicker
      // (see: https://github.com/stefangabos/Zebra_Datepicker).
      $datePicker.Zebra_DatePicker({
        always_visible: $datePickerWrapper,
        show_clear_date: false,
        show_icon: false,
        show_select_today: false,
        first_day_of_week: 0,
        format: dateFormat,
        onSelect: function (date, elements) {
          dateInput.value = !isMonthView ? date : date + '-01';
          formSubmit.click();
        },
        onChange: function () {
          if (isWeekView) {
            const dayPicker = document.querySelector('.dp_daypicker');
            dayPicker.classList.add('week');
            let dpCurrentSelection = document.querySelector('.dp_selected');
            if (!dpCurrentSelection) {
              dpCurrentSelection = document.querySelector('.dp_current');
            }
            dpCurrentSelection.closest('tr').classList.add('currentweek');
          }
        }
      });
    }
  };
})(document, Drupal, jQuery);
