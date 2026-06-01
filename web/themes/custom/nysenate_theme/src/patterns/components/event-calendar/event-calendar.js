/**
 * @file
 * Behaviors for the Event Calendar.
 */
!((document, Drupal, $) => {
  'use strict';

  /**
   * Write a message to the static aria-live region after a short delay.
   * The clear + 150 ms delay is required for Safari / VoiceOver to fire.
   */
  const announce = function (message) {
    const el = document.getElementById('events-results-announcement');
    if (!el) {
      return;
    }
    el.textContent = '';
    setTimeout(function () {
      el.textContent = message;
    }, 150);
  };

  const hideCalendarTablesFromAssistiveTech = function () {
    document.querySelectorAll('.Zebra_DatePicker .dp_daypicker, .Zebra_DatePicker .dp_header, .Zebra_DatePicker .dp_monthpicker, .Zebra_DatePicker .dp_yearpicker, .Zebra_DatePicker .dp_footer').forEach(function (table) {
      table.setAttribute('aria-hidden', 'true');
    });
  };

  /**
   * Setup and attach the Event Calendar behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.eventCalendar = {
    attach: function (context) {
      // After a Views AJAX filter refresh, Drupal re-calls attach() with the
      // newly rendered view element as context.  Announce the update here so
      // the live region (which lives outside the replaced element) is stable.
      if (context !== document) {
        const $ctx = $(context);
        const isEventsView = $ctx.hasClass('view-id-events') ||
          $ctx.find('.view-id-events').length > 0;
        if (isEventsView) {
          announce('Event results updated.');
        }
        return;
      }

      // Setup contextual variables.
      const isWeekView = document.querySelector('.view-display-id-page_3');
      const isMonthView = document.querySelector('.view-display-id-page_2');
      const dateFormat = !isMonthView ? 'Y-m-d' : 'Y-m';
      // Setup jQuery variables (Zebra_DatePicker has jQuery dependency).
      const $datePicker = $('#datepicker input');
      const $datePickerWrapper = $('#datepicker #container');
      if ($datePicker.length < 1) {
        return;
      }

      // Ensure the readonly date picker input has an accessible name.
      if (!$datePicker.attr('id')) {
        $datePicker.attr('id', 'events-datepicker-input');
      }
      $datePicker.attr('aria-label', 'Filter events by date');

      // Setup DOM pointers.
      const dateInput = document.querySelector('.calendar-events-form input.bef-datepicker');
      const formSubmit = document.querySelector('.calendar-events-form input.form-submit');

      // Announce updates triggered by exposed filters.
      const exposedForm = document.querySelector('.calendar-events-form form, form.calendar-events-form, .calendar-events-form');
      if (exposedForm && !exposedForm.dataset.a11yLiveFilterInit) {
        exposedForm.dataset.a11yLiveFilterInit = 'true';
        exposedForm.addEventListener('change', function () {
          announce('Updating event results.');
        }, true);
      }

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
          announce('Updating event results.');
          dateInput.value = !isMonthView ? date : date + '-01';
          formSubmit.click();
        },
        onChange: function () {
          hideCalendarTablesFromAssistiveTech();
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

      hideCalendarTablesFromAssistiveTech();
    }
  };
})(document, Drupal, jQuery);
