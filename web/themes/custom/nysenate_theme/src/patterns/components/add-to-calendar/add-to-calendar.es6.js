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

      dropdownToggle.on('keydown.addToCalendar', function (event) {
        if (event.key === 'Escape' && $(this).attr('aria-expanded') === 'true') {
          const dropdownContent = $(this).siblings('.add-to-calendar__dropdown');

          event.preventDefault();
          $(this).removeClass('active').attr('aria-expanded', 'false').focus();
          dropdownContent.removeClass('active').attr('aria-expanded', 'false');
        }
      });
    }
  };
})(document, Drupal, jQuery);
