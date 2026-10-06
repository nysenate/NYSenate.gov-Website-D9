/**
 * @file
 * Behaviors for the Add to Calendar.
 */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Add to Calendar behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.addToCalendar = {
    attach: function() {
      const dropdownToggle = $('.add-to-calendar__container');

      dropdownToggle.off('click.addToCalendar keydown.addToCalendar');
      dropdownToggle.on('click.addToCalendar', function () {
        const dropdownContent = $(this).siblings('.add-to-calendar__dropdown');
        const isExpanded = $(this).attr('aria-expanded') === 'true';

        $(this).toggleClass('active');
        $(this).attr('aria-expanded', isExpanded ? 'false' : 'true');

        dropdownContent.attr('aria-expanded', isExpanded ? 'false' : 'true');
        dropdownContent.toggleClass('active');
      });

      const calendar = dropdownToggle.parent('.add-to-calendar');
      calendar.off('keydown.addToCalendar');
      calendar.on('keydown.addToCalendar', function (event) {
        const toggle = $(this).children('.add-to-calendar__container');
        if (event.key === 'Escape' && toggle.attr('aria-expanded') === 'true') {
          const dropdownContent = toggle.siblings('.add-to-calendar__dropdown');

          event.preventDefault();
          toggle.removeClass('active').attr('aria-expanded', 'false').focus();
          dropdownContent.removeClass('active').attr('aria-expanded', 'false');
        }
      });
    }
  };
})(document, Drupal, jQuery);
