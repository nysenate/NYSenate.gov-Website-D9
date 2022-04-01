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

  Drupal.behaviors.cardListCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.card-list__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.card-list__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.card-list__arrow.arrow-left',
        nextArrow: '.card-list__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=card-list.es6.js.map
