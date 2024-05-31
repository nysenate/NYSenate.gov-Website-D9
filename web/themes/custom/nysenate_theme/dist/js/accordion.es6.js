/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Filter Accordion behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.accordion = {
    attach: function attach(context) {
      var self = this;
      var $accordions = $('.accordion', context);
      $accordions.each(function () {
        var $accordion = $(this); // Attach click handler for accordion.

        var $toggle = $accordion.find('.accordion__toggle', context);
        $toggle.on('click', function () {
          self.toggleClickEvent($accordion, $(this));
        });
      });
    },
    toggleClickEvent: function toggleClickEvent($accordion, $toggle) {
      // Identify the matching element.
      var $content = $accordion.find('#' + $toggle.attr('aria-controls'));

      if (!$accordion.hasClass('open')) {
        // Accordion does not have `.open`, so we are opening the accordion.
        $accordion.addClass('open'); // Toggle the `aria-expanded`.

        $toggle.attr('aria-expanded', 'true'); // Toggle the `aria-hidden` attribute on the content.

        $content.attr('aria-hidden', 'false');
      } else {
        // Same as the if, but in reverse.
        $accordion.removeClass('open');
        $toggle.attr('aria-expanded', 'false');
        $content.attr('aria-hidden', 'true');
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=accordion.es6.js.map
