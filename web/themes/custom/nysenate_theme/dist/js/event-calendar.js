/* eslint-disable no-underscore-dangle */

/* eslint-disable new-cap */

/* eslint-disable camelcase */

/* eslint-disable max-len */

/**
 * @file
 * Behaviors for the Event Calendar.
 */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Event Calendar behaviors.
   *
   * @type {Drupal~behavior}
   */
  // Set calendar filters labels

  Drupal.behaviors.eventCalendar = {
    attach: function attach() {
      if ($('#edit-field-date-value-value input').val()) {
        $('#datepicker input').val($('#edit-field-date-value-value input').val());
      }

      if ($('#edit-field-date-value-min input').val()) {
        $('#datepicker input').val($('#edit-field-date-value-min input').val());
      } // Init variables


      var viewType = '';
      var formatType = 'm/d/Y'; // Check the type of view i.e day/week/month and initialize datepicker options

      if ($('.view-calendar-page.view-display-id-page').length > 0) {
        viewType = 'day';
      }

      if ($('.view-calendar-page.view-display-id-month').length > 0) {
        viewType = 'month';
        formatType = 'm/Y';
      }

      if ($('.view-calendar-page.view-display-id-week').length > 0) {
        viewType = 'week';
      } // Initialize Zebra Datepicker


      $('#datepicker input').Zebra_DatePicker({
        always_visible: $('#container'),
        show_clear_date: false,
        show_icon: false,
        show_select_today: false,
        first_day_of_week: 0,
        format: formatType,
        onSelect: function onSelect(format) {
          var inputElement = '';

          if (viewType === 'week') {
            inputElement = $('#edit-field-date-value-min input');
          } else {
            inputElement = $('#edit-field-date-value-value input');
          }

          inputElement.val(format);
          inputElement.parents('form').submit();
        },
        onOpen: function onOpen() {
          this.trigger('change');

          var _text = $('.dp_header .dp_caption').html();

          var _selected = $('.dp_selected').html();

          var _month = _text.split(',');

          if (viewType === 'day') {
            $('.mobile-calendar-toggle').html('Viewing Day of ' + _month[0] + ' ' + _selected);
            $('.cal-nav-wrapper span.title').html(_month[0] + ' ' + _selected + ',' + _month[1]);
          }

          if (viewType === 'week') {
            var lastDayOfMonth = '';
            $('.currentweek td').each(function () {
              if (!$(this).hasClass('dp_not_in_month')) {
                lastDayOfMonth = $(this).html();
                return false;
              } else {
                var selectedDate = new Date(_text);
                previousMonth = new Date(selectedDate.setMonth(selectedDate.getMonth() - 1));
                _month[0] = previousMonth.toLocaleString('default', {
                  month: 'long'
                });
              }
            });
            $('.mobile-calendar-toggle').html('Viewing Week of ' + _month[0] + ' ' + $('.currentweek td:first').html());
            $('.cal-nav-wrapper span.title').html('Week of ' + _month[0] + ' ' + $('.currentweek td:first').html() + ',' + _month[1]);
          }

          if (viewType === 'month') {
            $('.mobile-calendar-toggle').html('Viewing month of ' + _month[0]);
            $('.cal-nav-wrapper span.title').html(_text);
          }

          $('.cal-nav-wrapper span.title').css('display', 'inline-block');
        },
        onChange: function onChange(view, elements) {
          var _selected = $('.dp_selected').html();

          if (_selected === null) {
            _selected = localStorage.getItem('selected');
            $('.dp_daypicker td').each(function () {
              if ($(this).html() === _selected) {
                $(this).addClass('dp_selected');
                $(this).closest('tr').addClass('currentweek');
                return;
              }
            });
          } else {
            localStorage.setItem('selected', $('.dp_selected').html());
          }

          elements.each(function () {
            if (viewType === 'week' && $(this)[0].className.match(/dp_selected$/)) {
              $(this).closest('tr').addClass('currentweek');
              $(this).addClass('dp_selected');
              $(this).parents('table').addClass('week');
            }
          });
        }
      }); // a bit of a hack to keep header the correct width.

      $('#datepicker .dp_header').css('width', '100%');
      $('.mobile-calendar-toggle').on('click', function () {
        $(this).hide();
        $(this).parent().find('#container .Zebra_DatePicker').show();
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=event-calendar.js.map
