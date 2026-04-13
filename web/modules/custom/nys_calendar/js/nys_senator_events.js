(function ($, Drupal, drupalSettings) {

    Drupal.behaviors.nys_senator_events = {
        attach: function (context, settings) {
            let $submit = $('#views-exposed-form-senator-events-upcoming-events input[type="submit"]');
            
            // Set active tab based on current filter value.
            var $eventPlace = $('select[name="field_event_place_value"]').val();
            $('.c-tab-link[data-value="' + $eventPlace + '"]').parent().addClass('active');

            once('nys-senator-events-next', '#next-month', context).forEach(function (el) {
                $(el).on('mousedown', function (e) {
                    if (e.button !== 0) return;
                    e.preventDefault();
                    $fd = $(this).data('first-day');
                    $ld = $(this).data('last-day');

                    var $real_min = $('input[name="field_date_range_value[min]"]', context);
                    var $real_max = $('input[name="field_date_range_value[max]"]', context);

                    let a = new Date($ld.toLocaleString());
                    a.setDate(a.getDate() + 1);

                    $real_min.val($fd.toLocaleString());
                    $real_max.val(a.toLocaleString());
                    const scrollPos = $(window).scrollTop();
                    $submit.click();
                    $(document).one('ajaxComplete', function () {
                        setTimeout(function () {
                            $(window).scrollTop(scrollPos);
                        }, 300);
                    });
                });
            });

            once('nys-senator-events-prev', '#previous-month', context).forEach(function (el) {
                $(el).on('mousedown', function (e) {
                    if (e.button !== 0) return;
                    e.preventDefault();
                    $fd = $(this).data('first-day');
                    $ld = $(this).data('last-day');

                    var $real_min = $('input[name="field_date_range_value[min]"]', context);
                    var $real_max = $('input[name="field_date_range_value[max]"]', context);

                    let a = new Date($ld.toLocaleString());
                    a.setDate(a.getDate() + 1);

                    $real_min.val($fd.toLocaleString());
                    $real_max.val(a.toLocaleString());
                    const scrollPos = $(window).scrollTop();
                    $submit.click();
                    $(document).one('ajaxComplete', function () {
                        setTimeout(function () {
                            $(window).scrollTop(scrollPos);
                        }, 300);
                    });
                });
            });

            once('nys-senator-events-tab', '.c-tab-link', context).forEach(function (el) {
                $(el).on('mousedown', function (e) {
                    if (e.button !== 0) return;
                    let field_event_place = $('select[name="field_event_place_value"]');
                    if ((!$(this).parents('.l-tab-bar').hasClass('open') || $(this).data('value') === field_event_place.val())
                        && $(window).innerWidth() < 760
                    ) {
                        $(this).parents('.l-tab-bar').toggleClass('open');
                    }
                    else {
                        const scrollPos = $(window).scrollTop();
                        field_event_place.val($(this).data('value'));
                        $submit.click();
                        // ViewsScrollTop animates scroll over 250ms after AJAX
                        // completes. Restore our position after it finishes.
                        $(document).one('ajaxComplete', function () {
                            setTimeout(function () {
                                $(window).scrollTop(scrollPos);
                            }, 300);
                        });
                    }
                });
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
