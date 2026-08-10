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
    attach: function (context) {
      // After a Views AJAX filter refresh, Drupal re-calls attach() with the
      // newly rendered view element as context.
      //
      // The exposed form (and its <select> elements) lives inside the view
      // wrapper that AJAX replaces, causing two problems:
      //
      // 1. FOCUS LOSS: The selected element is removed from the DOM mid-AJAX,
      //    dropping focus to <body>. VoiceOver announces the page title and
      //    landmark context before our announcement fires. Fix: move focus to
      //    a silent aria-hidden anchor BEFORE AJAX fires.
      //
      // 2. STALE LISTENERS / DATASET: Listeners bound to the form element and
      //    dataset flags on it are destroyed with the replaced DOM. Fix:
      //    delegate from document using stable name attributes, and use a
      //    module-scoped flag rather than dataset on the replaced element.
      if (context !== document) {
        const view = (context.classList && context.classList.contains('view-id-events'))
          ? context
          : (context.querySelector && context.querySelector('.view-id-events'));
        if (!view) return;

        // Restore focus to the filter that triggered this refresh.
        if (window._eventCalendarLastFilterName) {
          const target = view.querySelector('[name="' + window._eventCalendarLastFilterName + '"]');
          if (target) target.focus({ preventScroll: true });
          window._eventCalendarLastFilterName = null;
        }

        // Count event results using the row element from the unformatted row
        // template (views-view-unformatted--events.html.twig), which wraps each
        // result in article.c-event-block. This is distinct from the permanent
        // article.c-block--initiative promo blocks which are always in the DOM.
        const count = view.querySelectorAll('.view-content article.c-event-block').length;
        if (view.querySelector('.view-empty')) {
          Drupal.announce('Results updated: no events match the selected filters.');
        }
        else {
          Drupal.announce('Results updated: ' + count + ' event' + (count !== 1 ? 's' : '') + ' shown.');
        }

        // IMPORTANT: Do NOT return here. The Zebra_DatePicker must be
        // re-initialized with fresh DOM references after each AJAX replace
        // because .calendar-events-form (and its inputs) lives inside the
        // replaced view wrapper. The guard below prevents duplicate delegated
        // listeners; the datepicker init always runs to refresh its closures.
      }

      // Add document-level delegated listeners only once — they survive AJAX
      // because they are bound to document, not to the replaced form elements.
      if (!window._eventCalendarListenersAttached) {
        window._eventCalendarListenersAttached = true;

        // Silent focus anchor outside the AJAX-replaced view.
        // WAI-ARIA 1.2: aria-hidden MUST NOT be set on focusable elements
        // (including tabindex="-1"). Omitting it here; CSS visual hiding is
        // sufficient to keep this anchor invisible to sighted users, and
        // VoiceOver will announce nothing for an empty, unlabeled element.
        const focusHolder = document.createElement('div');
        focusHolder.setAttribute('tabindex', '-1');
        focusHolder.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;';
        document.body.appendChild(focusHolder);

        // Delegate using name attributes — stable across AJAX ID refreshes.
        // Views auto-submits on change (submit has js-hide class).
        $(document).on('change.eventCalendar', 'select[name="type"], select[name="field_committee_target_id"], select[name="active_senators_filter"]', function () {
          window._eventCalendarLastFilterName = this.name;
          focusHolder.focus({ preventScroll: true });
          Drupal.announce('Updating event results.');
        });
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

      // Setup DOM pointers.
      const dateInput = document.querySelector('.calendar-events-form input.bef-datepicker');
      const formSubmit = document.querySelector('.calendar-events-form input.form-submit');
      // jQuery 4 removed $.isArray and $.trim; restore them for Zebra_DatePicker compatibility.
      $.isArray = $.isArray || Array.isArray;
      $.trim = $.trim || function (str) { return str == null ? '' : String.prototype.trim.call(str); };
      // Initiate Zebra_DatePicker
      // (see: https://github.com/stefangabos/Zebra_Datepicker).
      $datePicker.Zebra_DatePicker({
        always_visible: $datePickerWrapper,
        show_clear_date: false,
        show_icon: false,
        show_select_today: false,
        first_day_of_week: 0,
        format: dateFormat,
        onSelect: function (date) {
          Drupal.announce('Updating event results.');
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
