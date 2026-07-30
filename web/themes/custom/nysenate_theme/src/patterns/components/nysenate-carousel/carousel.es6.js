/**
 * @file
 * Behaviors for the Filter Accordion.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  // jQuery 4 removed $.type; restore it for Slick compatibility.
  $.type = $.type || function(obj) {
    if (obj === null) return 'null';
    if (obj === undefined) return 'undefined';
    if (Array.isArray(obj)) return 'array';
    return typeof obj;
  };

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
