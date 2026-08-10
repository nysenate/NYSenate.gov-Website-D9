/**
 * @file
 * Behaviors for the Filter Accordion.
 */
/* eslint-disable max-len */

!((document, Drupal, $, once) => {
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
  Drupal.behaviors.quoteCarousel = {
    attach: function (context) {
      $(once('quoteCarousel', '.quote-carousel__slick', context))
        .each(function() {
          let $nav = $(this).parent().find('.slick-pager');
          $(this).slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: true,
            fade: false,
            appendArrows: $nav,
          });
        });
    }
  };

})(document, Drupal, jQuery, once);
