/**
 * @file
 * Behaviors for the Filter Accordion.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.carousel = {
    attach: function attach() {
      $('.carousel__slick').not('.slick-initialized').slick({
        dots: true
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=carousel.es6.js.map
