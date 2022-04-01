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
  Drupal.behaviors.productCardCarousel = {
    attach: function () {
      this.slickCardCarousel();

      $(window).on('resize', function() {
        $('.product-card__carousel').slick('resize');
      });
    },
    slickCardCarousel: function() {
      $('.product-card__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.product-card__arrow.arrow-left',
        nextArrow: '.product-card__arrow.arrow-right',
        mobileFirst: true,
        responsive: [
          {
            breakpoint: 768,
            settings: 'unslick'
          },
        ],
      });
    },
  };

})(document, Drupal, jQuery);
