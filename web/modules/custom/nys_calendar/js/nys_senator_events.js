(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.nys_senator_events = {
    attach: function (context, settings) {
      let $submit = $('#views-exposed-form-senator-events-upcoming-events input[type="submit"]');
      let $current_month_year = $('input[name="current_month_year"]');
      let $first_day_of_month = $('input[name="first_day_of_month"]');
      let $last_day_of_month = $('input[name="last_day_of_month"]');
      let $next_month = $('input[name="next_month"]');
      let $previous_month = $('input[name="previous_month"]');

      console.log($current_month_year)
      $submit.closest('.form-actions').hide();
      $('#views-exposed-form-senator-events-upcoming-events fieldset', context).hide();
      $('#views-exposed-form-senator-events-upcoming-events .form-item-field-event-place-value', context).hide();

      function submitDateValues() {
        var $real_min = $('input[name="field_date_range_value[min]"]', context);
        var $real_max = $('input[name="field_date_range_value[max]"]', context);

        $real_min.val($first_day_of_month.val());
        $real_max.val($last_day_of_month.val());
        $submit.click();
      }

      $('html', context).once('submitDefaultValues').each(function(i) {
        submitDateValues();
      });

      $('#next-month').on('mousedown', function (e) {
        e.preventDefault();
        $fd = $(this).data('first-day');
        $ld = $(this).data('last-day');

        var $real_min = $('input[name="field_date_range_value[min]"]', context);
        var $real_max = $('input[name="field_date_range_value[max]"]', context);

        $real_min.val($fd);
        $real_max.val($ld);
        $submit.click();
      });

      $('#previous-month').on('mousedown', function (e) {
        e.preventDefault();
        $fd = $(this).data('first-day');
        $ld = $(this).data('last-day');

        var $real_min = $('input[name="field_date_range_value[min]"]', context);
        var $real_max = $('input[name="field_date_range_value[max]"]', context);

        $real_min.val($fd);
        $real_max.val($ld);
        $submit.click();
      });

      let $tabFilters = context.querySelectorAll('.c-tab-link');
      if ($tabFilters && $tabFilters.length > 0) {
        $tabFilters.forEach(function (element) {
          $(element).on('mousedown', function (e) {
            $('select[name="field_event_place_value"]').val($(this).data('value'));
            $submit.click();
          })
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
