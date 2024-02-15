/**
 * @file
 * Behaviors for the How Senate Works.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the How Senate Works behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.howSenateWorks = {
    attach: function attach(context) {
      if (context !== document) {
        return;
      }

      var self = this;
      var carouselAnimating = false;
      var carouselNavBtn = $('.c-carousel--how-senate-works.c-carousel--nav .c-carousel--btn', context);

      if ($('#js-carousel-budget').length > 0) {
        $('#js-carousel-budget').hammer().on('swipe', function (event) {
          self.carouselAdvance(event, carouselAnimating, self, $(this));
        });
      }

      if ($('#js-carousel-law').length > 0) {
        $('#js-carousel-law').hammer().on('swipe', function (event) {
          self.carouselAdvance(event, carouselAnimating, self, $(this));
        });
      }

      carouselNavBtn.on('click', Drupal.debounce(function (event) {
        self.carouselAdvance(event, carouselAnimating, self, $(this));
      }, 300));
    },
    carouselAdvance: function carouselAdvance(e, carouselAnimating, self, item) {
      var PREV_VALUE = 4;
      var NEXT_VALUE = 2;

      if (carouselAnimating) {
        return;
      }

      var nav;
      var newPos;
      var activeElem; // the nav is a different relationship if we're touch

      if (e.direction === PREV_VALUE || e.direction === NEXT_VALUE) {
        nav = $(e.target).parents('.js-carousel').siblings('.c-carousel--nav');
      } else {
        nav = item.parent('.c-carousel--nav');
      }

      var wrap = nav.parent();
      var carousel = wrap.find('.js-carousel');
      var itemAmt = carousel.children().length;
      var itemWidth = carousel.width() / itemAmt;
      var carouselPos = parseInt(carousel.css('left')); // if the previous button is hidden - do not move that way or at all

      if (e.direction === PREV_VALUE && nav.children('.prev').hasClass('hidden')) {
        return false;
      } // if the next button is hidden - do not move that way or at all
      else if (e.direction === NEXT_VALUE && nav.children('.next').hasClass('hidden')) {
          return false;
        } else {
          e.preventDefault();
        } // set flag to stop button jammers


      carouselAnimating = true;

      var setCarouselAnimating = function setCarouselAnimating() {
        carouselAnimating = false;
      }; // logic to set directionaltiy and left offset of carousel


      if (item.hasClass('prev') || e.direction === PREV_VALUE) {
        newPos = carouselPos + itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth - 1;
        self.checkCarouselBtns(nav, activeElem, itemAmt);
      } else if (item.hasClass('next') || e.direction === NEXT_VALUE) {
        newPos = carouselPos - itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth + 1;
        self.checkCarouselBtns(nav, activeElem, itemAmt);
      }

      carousel.css({
        left: newPos
      }); // settimeout based on length of css transition -- stops button jammers

      setTimeout(setCarouselAnimating, 300);
    },
    checkCarouselBtns: function checkCarouselBtns(nav, activeElem, itemAmt) {
      // logic to toggle visiblity of btns
      if (activeElem > 0) {
        nav.children('.prev').addClass('visible').removeClass('hidden');
      } else if (activeElem < 1) {
        nav.children('.prev').addClass('hidden').removeClass('visible');
      }

      if (activeElem >= itemAmt - 1) {
        nav.children('.next').addClass('hidden').removeClass('visible');
      } else if (activeElem <= itemAmt - 1) {
        nav.children('.next').addClass('visible').removeClass('hidden');
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=how-senate-works.es6.js.map
