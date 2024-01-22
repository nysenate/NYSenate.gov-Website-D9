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

  Drupal.behaviors.eventCarousel = {
    attach: function attach(context) {
      // We want to only have the slick carousel trigger once or we will
      // have a lot of pagers being appended on ajax trigger.
      $('.event-carousel', context).once('eventCarousel').each(function () {
        var $parentContainer = $(this);
        var $list = $('.event-carousel__list', $parentContainer);
        var $nav = $(this).closest('.event-carousel').find('.slick-pager');
        $list.slick({
          slidesToShow: 3,
          slidesToScroll: 1,
          arrows: true,
          fade: false,
          appendArrows: $nav,
          infinite: false,
          responsive: [{
            // Not sure why the width being 1024 does not trigger this break
            // point, when showing a 1024 wide desktop screen.
            breakpoint: 1025,
            settings: {
              slidesToShow: 2
            }
          }, {
            breakpoint: 768,
            settings: {
              slidesToShow: 1
            }
          }]
        });
        $list.on('afterChange', function (event, slick) {
          var isLastSlide = slick.$nextArrow.hasClass('slick-disabled');
          $parentContainer.toggleClass('fade', !isLastSlide);
        });
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=event-carousel.es6.js.map
