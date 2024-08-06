/* eslint-disable no-underscore-dangle */
/* eslint-disable new-cap */
/* eslint-disable camelcase */
/* eslint-disable max-len */
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
  // Set calendar filters labels
  Drupal.behaviors.eventCalendar = {
    attach: function () {
      // Init variables
      var viewType = '';
      var formatType = 'Y-m-d';
      var submit = '';

      // Check the type of view i.e day/week/month and initialize datepicker options
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
        formatType = 'Y-m';
        submit = $('#views-exposed-form-events-page-2 input[type="submit"]');
        $('.form-item-date.js-form-type-date').hide();
      }
      if ($('.view-events.view-display-id-page_3').length > 0) {
        viewType = 'week';
        submit = $('#views-exposed-form-events-page-3 input[type="submit"]');
        formatType = 'm/d/Y';
        $('.form-item-date-min.form-type-date').hide();
      }

      if ($('.form-item-date.js-form-type-date input').val()) {
        if (viewType === 'day') {
          $('#datepicker input').val($('.form-item-date.js-form-type-date input').val());
        }
        else if (viewType === 'month') {
          const splitDate = $('.form-item-date.js-form-type-date input')
            .val()
            .split('-');

          $('#datepicker input').val(`${splitDate[0]}-${splitDate[1]}`);
        }
      }
      if ($('.js-form-item-date-min input').val()) {
        const splitDate = $('.js-form-item-date-min input').val().split('-');

        if (splitDate.length > 1) {
          $('.js-form-item-date-min input').val(`${splitDate[1]}/${splitDate[2]}/${splitDate[0]}`);
          $('#datepicker input').val(`${splitDate[1]}/${splitDate[2]}/${splitDate[0]}`);
        }
        else {
          $('.js-form-item-date-min input').val(splitDate[0]);
          $('#datepicker input').val(splitDate[0]);
        }
      }
      if ($('.js-form-item-date-max input').val()) {
        const splitDate = $('.js-form-item-date-max input').val().split('-');

        if (splitDate.length > 1) {
          $('.js-form-item-date-max input').val(`${splitDate[1]}/${splitDate[2]}/${splitDate[0]}`);
        }
        else {
          $('.js-form-item-date-max input').val(splitDate[0]);
        }
      }

      // Initialize Zebra Datepicker
      $('#datepicker input').Zebra_DatePicker({
        always_visible: $('#container'),
        show_clear_date: false,
        show_icon: false,
        show_select_today: false,
        first_day_of_week: 0,
        format: formatType,
        onSelect: function (format) {
          var inputElement = '';
          if (viewType === 'week') {
            inputElement = $('.js-form-item-date-min input');
          }
          else {
            inputElement = $('.form-item-date.js-form-type-date input');
          }
          if (viewType === 'month') {
            inputElement.val(format + '-01');
            submit.click();
          }
          if (viewType === 'week') {
            const splitDate = format.split('/');
            inputElement.val(`${splitDate[2]}-${splitDate[0]}-${splitDate[1]}`);
            submit.click();
          }
          else {
            inputElement.val(format);
            submit.click();
          }
        },
        onOpen: function () {
          this.trigger('change');
          var _text = $('.dp_header .dp_caption').html();
          var _selected = $('.dp_selected').html();
          var _current = $('.dp_current').html();
          var _month = _text.split(',');
          if (viewType === 'day') {
            if (_selected) {
              $('.mobile-calendar-toggle').html(
                'Viewing Day of ' + _month[0] + ' ' + _selected
              );
              $('.cal-nav-wrapper span.title').html(
                _month[0] + ' ' + _selected + ',' + _month[1]
              );
            }
            else {
              $('.mobile-calendar-toggle').html(
                'Viewing Day of ' + _month[0] + ' ' + _current
              );
              $('.cal-nav-wrapper span.title').html(
                _month[0] + ' ' + _current + ',' + _month[1]
              );
            }
          }
          if (viewType === 'week') {
            $('.currentweek td').each(function () {
              if ($(this).hasClass('dp_not_in_month')) {
                var selectedDate = new Date(_text);
                var previousMonth = new Date(
                  selectedDate.setMonth(selectedDate.getMonth() - 1)
                );
                _month[0] = previousMonth.toLocaleString('default', {
                  month: 'long'
                });
              }
            });
            $('.mobile-calendar-toggle').html(
              'Viewing Week of ' +
                _month[0] +
                ' ' +
                $('.currentweek td:first').html()
            );
            $('.cal-nav-wrapper span.title').html(
              'Week of ' +
                _month[0] +
                ' ' +
                $('.currentweek td:first').html() +
                ',' +
                _month[1]
            );
          }
          if (viewType === 'month') {
            $('.mobile-calendar-toggle').html('Viewing month of ' + _month[0]);
            $('.cal-nav-wrapper span.title').html(_text);
          }
          $('.cal-nav-wrapper span.title').css('display', 'inline-block');
        },
        onChange: function (view, elements) {
          var _selected = $('.dp_selected').html();

          if (_selected === null || _selected === undefined) {
            $('.dp_current').addClass('dp_selected');
            $('.dp_daypicker td').each(function () {
              if ($(this).html() === _selected) {
                var inputElement = '';

                if (viewType === 'week') {
                  inputElement = $('.js-form-item-date-min input');
                }
                else {
                  inputElement = $('.form-item-date.js-form-type-date input');
                }

                if (inputElement.val() === '') {
                  const placeholder = inputElement.attr('placeholder');
                  const splitDate = placeholder.split('/');

                  inputElement.val(`${splitDate[0]}/${_selected}/${splitDate[2]}`);
                }

                if (!$(this).hasClass('dp_current')) {
                  $(this).addClass('dp_selected');
                }

                $(this).closest('tr').addClass('currentweek');
                return;
              }
              else {
                $(this).removeClass('dp_selected');
              }
            });
          }
          elements.each(function () {
            if (
              viewType === 'week' &&
              $(this).hasClass('dp_selected')
            ) {
              $(this).closest('tr').addClass('currentweek');
              $(this).addClass('dp_selected');
              $(this).parents('table').addClass('week');
            }
          });
        }
      });
      // a bit of a hack to keep header the correct width.
      $('#datepicker .dp_header').css('width', '100%');
      $('.mobile-calendar-toggle').on('click', function () {
        $(this).hide();
        $(this).parent().find('#container .Zebra_DatePicker').show();
      });
    }
  };
})(document, Drupal, jQuery);
