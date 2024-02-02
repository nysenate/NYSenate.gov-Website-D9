/**
 * @file
 * Behaviors for the How Senate Works.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the How Senate Works behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.howSenateWorks = {
    attach: function (context) {
      if (context !== document) {
        return;
      }
      const self = this;
      let carouselAnimating = false;
      const carouselNavBtn = $(
        '.c-carousel--how-senate-works.c-carousel--nav .c-carousel--btn',
        context
      );

      if ($('#js-carousel-budget').length > 0) {
        $('#js-carousel-budget')
          .hammer()
          .on('swipe', function (event) {
            self.carouselAdvance(event, carouselAnimating, self, $(this));
          });
      }
      if ($('#js-carousel-law').length > 0) {
        $('#js-carousel-law')
          .hammer()
          .on('swipe', function (event) {
            self.carouselAdvance(event, carouselAnimating, self, $(this));
          });
      }

      carouselNavBtn.on(
        'click',
        Drupal.debounce(function (event) {
          self.carouselAdvance(event, carouselAnimating, self, $(this));
        }, 300)
      );
    },
    carouselAdvance: function (e, carouselAnimating, self, item) {
      const PREV_VALUE = 4;
      const NEXT_VALUE = 2;

      if (carouselAnimating) {
        return;
      }

      let nav;
      let newPos;
      let activeElem;

      // the nav is a different relationship if we're touch
      if (e.direction === PREV_VALUE || e.direction === NEXT_VALUE) {
        nav = $(e.target).parents('.js-carousel').siblings('.c-carousel--nav');
      }
      else {
        nav = item.parent('.c-carousel--nav');
      }

      const wrap = nav.parent();
      const carousel = wrap.find('.js-carousel');
      const itemAmt = carousel.children().length;
      const itemWidth = carousel.width() / itemAmt;
      const carouselPos = parseInt(carousel.css('left'));

      // if the previous button is hidden - do not move that way or at all
      if (e.direction === PREV_VALUE && nav.children('.prev').hasClass('hidden')) {
        return false;
      }
      // if the next button is hidden - do not move that way or at all
      else if (e.direction === NEXT_VALUE && nav.children('.next').hasClass('hidden')) {
        return false;
      }
      else {
        e.preventDefault();
      }

      // set flag to stop button jammers
      carouselAnimating = true;

      const setCarouselAnimating = function () {
        carouselAnimating = false;
      };

      // logic to set directionaltiy and left offset of carousel
      if (item.hasClass('prev') || e.direction === PREV_VALUE) {
        newPos = carouselPos + itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth - 1;

        self.checkCarouselBtns(nav, activeElem, itemAmt);
      }
      else if (item.hasClass('next') || e.direction === NEXT_VALUE) {
        newPos = carouselPos - itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth + 1;

        self.checkCarouselBtns(nav, activeElem, itemAmt);
      }

      carousel.css({
        left: newPos
      });

      // settimeout based on length of css transition -- stops button jammers
      setTimeout(setCarouselAnimating, 300);
    },
    checkCarouselBtns: function (nav, activeElem, itemAmt) {
      // logic to toggle visiblity of btns
      if (activeElem > 0) {
        nav.children('.prev').addClass('visible').removeClass('hidden');
      }
      else if (activeElem < 1) {
        nav.children('.prev').addClass('hidden').removeClass('visible');
      }

      if (activeElem >= itemAmt - 1) {
        nav.children('.next').addClass('hidden').removeClass('visible');
      }
      else if (activeElem <= itemAmt - 1) {
        nav.children('.next').addClass('visible').removeClass('hidden');
      }
    }
  };
})(document, Drupal, jQuery);
