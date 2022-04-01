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

  Drupal.behaviors.blogCardCarousel = {
    attach: function attach() {
      this.slickCardCarousel();
      $(window).on('resize', function () {
        $('.blog-card__carousel').slick('resize');
      });
    },
    slickCardCarousel: function slickCardCarousel() {
      $('.blog-card__carousel').slick({
        dots: false,
        infinite: false,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: '.blog-card__arrow.arrow-left',
        nextArrow: '.blog-card__arrow.arrow-right',
        mobileFirst: true,
        responsive: [{
          breakpoint: 768,
          settings: 'unslick'
        }]
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=blog-card.es6.js.map
