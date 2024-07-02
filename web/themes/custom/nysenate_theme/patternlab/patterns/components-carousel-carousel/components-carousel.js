/**
 * @file
 * Behaviors for the Filter Accordion.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Carousel behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.carousel = {
    attach: function() {
      $('.carousel__slick')
        .not('.slick-initialized')
        .slick({
          infinite: false,
          adaptiveHeight: true
        });
    }
  };
})(document, Drupal, jQuery);
