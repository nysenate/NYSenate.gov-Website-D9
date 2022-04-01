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

  Drupal.behaviors.galleryCarousel = {
    attach: function attach() {
      $('.gallery-carousel').not('.slick-initialized').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: '.gallery-carousel__nav',
        infinite: false
      });
      $('.gallery-carousel__nav').not('.slick-initialized').slick({
        slidesToShow: 5,
        slidesToScroll: 1,
        asNavFor: '.gallery-carousel',
        focusOnSelect: true,
        arrows: false,
        infinite: false,
        responsive: [{
          /* xsm breakpoint equivalent */
          breakpoint: 640,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 3,
            infinite: false
          }
        }]
      });
      $('.arrow-left').click(function () {
        $('.gallery-carousel').slick('slickPrev');
      });
      $('.arrow-right').click(function () {
        $('.gallery-carousel').slick('slickNext');
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=gallery-carousel.es6.js.map
