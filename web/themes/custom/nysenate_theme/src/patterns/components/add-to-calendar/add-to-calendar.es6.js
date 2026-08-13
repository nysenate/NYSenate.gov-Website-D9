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

      dropdownToggle.on('click', function () {
        const dropdownContent = $(this).find('.add-to-calendar__dropdown');
        const isExpanded = $(this).attr('aria-expanded') === 'true';

        $(this).toggleClass('active');
        $(this).attr('aria-expanded', isExpanded ? 'false' : 'true');

        dropdownContent.attr('aria-expanded', isExpanded ? 'false' : 'true');
        dropdownContent.toggleClass('active');
      });
    }
  };
})(document, Drupal, jQuery);
