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
      // Init variables
      var viewType = '';
      var formatType = 'm/d/Y';
      var submit = ''; // Check the type of view i.e day/week/month and initialize datepicker options

      if ($('.view-events.view-display-id-day_block').length > 0) {
        viewType = 'day';
        submit = $('#views-exposed-form-events-day-block input[type="submit"]');
      }

      if ($('.view-events.view-display-id-page_1').length > 0) {
        viewType = 'day';
        submit = $('#views-exposed-form-events-page_1 input[type="submit"]');
      }

      if ($('.view-events.view-display-id-page_2').length > 0) {
        viewType = 'month';
        formatType = 'm/Y';
        submit = $('#views-exposed-form-events-page-2 input[type="submit"]');
        $('.form-item-date.js-form-type-textfield').hide();
      }

      if ($('.view-events.view-display-id-page_3').length > 0) {
        viewType = 'week';
        submit = $('#views-exposed-form-events-page-3 input[type="submit"]');
        $('.form-item-date-min.form-type-textfield').hide();
      }

      if ($('.form-item-date.js-form-type-textfield input').val()) {
        if (viewType === 'day') {
          $('#datepicker input').val($('.form-item-date.js-form-type-textfield input').val());
        } else if (viewType === 'month') {
          var splitDate = $('.form-item-date.js-form-type-textfield  input').val().split('/');
          var lastIndex = splitDate.length - 1;
          $('#datepicker input').val("".concat(splitDate[0], "/").concat(splitDate[lastIndex]));
        }
      }

      if ($('.js-form-item-date-min input').val()) {
        var _splitDate = $('.js-form-item-date-min input').val().split('-');

        if (_splitDate.length > 1) {
          $('.js-form-item-date-min input').val("".concat(_splitDate[1], "/").concat(_splitDate[2], "/").concat(_splitDate[0]));
          $('#datepicker input').val("".concat(_splitDate[1], "/").concat(_splitDate[2], "/").concat(_splitDate[0]));
        } else {
          $('.js-form-item-date-min input').val(_splitDate[0]);
          $('#datepicker input').val(_splitDate[0]);
        }
      }

      if ($('.js-form-item-date-max input').val()) {
        var _splitDate2 = $('.js-form-item-date-max input').val().split('-');

        if (_splitDate2.length > 1) {
          $('.js-form-item-date-max input').val("".concat(_splitDate2[1], "/").concat(_splitDate2[2], "/").concat(_splitDate2[0]));
        } else {
          $('.js-form-item-date-max input').val(_splitDate2[0]);
        }
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
            inputElement = $('.js-form-item-date-min input');
          } else {
            inputElement = $('.form-item-date.js-form-type-textfield input');
          }

          if (viewType === 'month') {
            // Temporarily set date value to avoid conflict with the views form.
            var _splitDate3 = format.split('/');

            inputElement.val("".concat(_splitDate3[0], "/01/").concat(_splitDate3[1]));
            submit.click();
            inputElement.val("".concat(_splitDate3[0], "/").concat(_splitDate3[1]));
          } else {
            inputElement.val(format);
            submit.click();
          }
        },
        onOpen: function onOpen() {
          this.trigger('change');

          var _text = $('.dp_header .dp_caption').html();

          var _selected = $('.dp_selected').html();

          var _current = $('.dp_current').html();

          var _month = _text.split(',');

          if (viewType === 'day') {
            if (_selected) {
              $('.mobile-calendar-toggle').html('Viewing Day of ' + _month[0] + ' ' + _selected);
              $('.cal-nav-wrapper span.title').html(_month[0] + ' ' + _selected + ',' + _month[1]);
            } else {
              $('.mobile-calendar-toggle').html('Viewing Day of ' + _month[0] + ' ' + _current);
              $('.cal-nav-wrapper span.title').html(_month[0] + ' ' + _current + ',' + _month[1]);
            }
          }

          if (viewType === 'week') {
            $('.currentweek td').each(function () {
              if ($(this).hasClass('dp_not_in_month')) {
                var selectedDate = new Date(_text);
                var previousMonth = new Date(selectedDate.setMonth(selectedDate.getMonth() - 1));
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

          if (_selected === null || _selected === undefined) {
            $('.dp_current').addClass('dp_selected');
            $('.dp_daypicker td').each(function () {
              if ($(this).html() === _selected) {
                var inputElement = '';

                if (viewType === 'week') {
                  inputElement = $('.js-form-item-date-min input');
                } else {
                  inputElement = $('.form-item-date.js-form-type-textfield input');
                }

                if (inputElement.val() === '') {
                  var placeholder = inputElement.attr('placeholder');

                  var _splitDate4 = placeholder.split('/');

                  inputElement.val("".concat(_splitDate4[0], "/").concat(_selected, "/").concat(_splitDate4[2]));
                }

                if (!$(this).hasClass('dp_current')) {
                  $(this).addClass('dp_selected');
                }

                $(this).closest('tr').addClass('currentweek');
                return;
              } else {
                $(this).removeClass('dp_selected');
              }
            });
          }

          elements.each(function () {
            if (viewType === 'week' && $(this).hasClass('dp_selected')) {
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
