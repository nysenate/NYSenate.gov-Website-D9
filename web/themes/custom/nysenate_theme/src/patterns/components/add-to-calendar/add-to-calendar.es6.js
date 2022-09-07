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

        $(this).toggleClass('active');

        dropdownContent.attr('aria-expanded', function(index, attr) {
          return attr === 'true' ? 'false' : 'true';
        });
        dropdownContent.toggleClass('active');
      });
    }
  };
})(document, Drupal, jQuery);
