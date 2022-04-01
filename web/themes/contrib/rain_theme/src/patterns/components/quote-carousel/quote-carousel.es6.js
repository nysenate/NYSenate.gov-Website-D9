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
  Drupal.behaviors.quoteCarousel = {
    attach: function (context) {
      $('.quote-carousel__slick', context).once('quoteCarousel')
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

})(document, Drupal, jQuery);
