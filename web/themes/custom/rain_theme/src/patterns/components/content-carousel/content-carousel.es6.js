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
  Drupal.behaviors.contentCarousel = {
    attach: function (context) {
      // We want to only have the slick carousel trigger once or we will
      // have a lot of pagers being appended on ajax trigger.
      $('.content-carousel', context).once('contentCarousel').each(function () {
        const $parentContainer = $(this);
        const $list = $('.content-carousel__list', $parentContainer);
        let $nav = $(this).closest('.content-carousel').find('.slick-pager');
        $list.slick({
          slidesToShow: 3,
          slidesToScroll: 1,
          arrows: true,
          fade: false,
          appendArrows: $nav,
          infinite: false,
          responsive: [
            {
              // Not sure why the width being 1024 does not trigger this break
              // point, when showing a 1024 wide desktop screen.
              breakpoint: 1025,
              settings: {
                slidesToShow: 2,
              },
            },
            {
              breakpoint: 768,
              settings: {
                slidesToShow: 1,
              },
            },
          ],
        });
        $list.on('afterChange', function (event, slick) {
          const isLastSlide = slick.$nextArrow.hasClass('slick-disabled');
          $parentContainer.toggleClass('fade', !isLastSlide);
        });
      });
    }
  };
})(document, Drupal, jQuery);
