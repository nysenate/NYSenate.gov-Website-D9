(function ($, Drupal, drupalSettings) {

    Drupal.behaviors.nys_senator_events = {
        attach: function (context, settings) {
            let $submit = $('#views-exposed-form-senator-events-upcoming-events input[type="submit"]');
            
            // Set active tab based on current filter value.
            var $eventPlace = $('select[name="field_event_place_value"]').val();
            $('.c-tab-link[data-value="' + $eventPlace + '"]').parent().addClass('active');

            $('#next-month').on(
                'mousedown', function (e) {
                    e.preventDefault();
                    $fd = $(this).data('first-day');
                    $ld = $(this).data('last-day');

                    var $real_min = $('input[name="field_date_range_value[min]"]', context);
                    var $real_max = $('input[name="field_date_range_value[max]"]', context);

                  let a = new Date($ld.toLocaleString());
                  a.setDate(a.getDate() + 1);

                  $real_min.val($fd.toLocaleString());
                  $real_max.val(a.toLocaleString());
                  $submit.click();
                }
            );

            $('#previous-month').on(
                'mousedown', function (e) {
                    e.preventDefault();
                    $fd = $(this).data('first-day');
                    $ld = $(this).data('last-day');

                    var $real_min = $('input[name="field_date_range_value[min]"]', context);
                    var $real_max = $('input[name="field_date_range_value[max]"]', context);

                  let a = new Date($ld.toLocaleString());
                  a.setDate(a.getDate() + 1);

                  $real_min.val($fd.toLocaleString());
                  $real_max.val(a.toLocaleString());
                  $submit.click();
                }
            );

            let $tabFilters = context.querySelectorAll('.c-tab-link');
            if ($tabFilters && $tabFilters.length > 0) {
                $tabFilters.forEach(
                    function (element) {
                        $(element).on(
                            'mousedown', function (e) {
                                let field_event_place = $('select[name="field_event_place_value"]');
                                if ((!$(this).parents('.l-tab-bar').hasClass('open') || $(this).data('value') === field_event_place.val())
                                    && $(window).innerWidth() < 760
                                ) {
                                      $(this).parents('.l-tab-bar').toggleClass('open');
                                }
                                else {
                                    field_event_place.val($(this).data('value'));
                                    $submit.click();
                                }
                            }
                        )
                    }
                );
            }
        }
    };

})(jQuery, Drupal, drupalSettings);
