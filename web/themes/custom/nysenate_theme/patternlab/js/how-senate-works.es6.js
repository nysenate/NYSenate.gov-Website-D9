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
    attach: function attach() {
      var self = this;
      var carouselAnimating = false;
      var carouselNavBtn = $('.c-carousel--nav .c-carousel--btn');

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

      carouselNavBtn.on('click', function (event) {
        self.carouselAdvance(event, carouselAnimating, self, $(this));
      });
    },
    carouselAdvance: function carouselAdvance(e, carouselAnimating, self, item) {
      if (carouselAnimating) {
        return;
      }

      var nav;
      var newPos;
      var activeElem; // the nav is a different relationship if we're touch

      if (e.direction === 4 || e.direction === 2) {
        nav = $(e.target).parents('.js-carousel').siblings('.c-carousel--nav');
      } else {
        nav = item.parent('.c-carousel--nav');
      }

      var wrap = nav.parent();
      var carousel = wrap.find('.js-carousel');
      var itemAmt = carousel.children().length;
      var itemWidth = carousel.width() / itemAmt;
      var carouselPos = parseInt(carousel.css('left')); // if the previous button is hidden - do not move that way or at all

      if (e.direction === 4 && nav.children('.prev').hasClass('hidden')) {
        return false;
      } // if the next button is hidden - do not move that way or at all
      else if (e.direction === 2 && nav.children('.next').hasClass('hidden')) {
          return false;
        } else {
          e.preventDefault();
        } // set flag to stop button jammers


      carouselAnimating = true;

      var setCarouselAnimating = function setCarouselAnimating() {
        carouselAnimating = false;
      }; // logic to set directionaltiy and left offset of carousel


      if (item.hasClass('prev') || e.direction === 4) {
        newPos = carouselPos + itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth - 1;
        self.checkCarouselBtns(nav, activeElem, itemAmt);
      } else if (item.hasClass('next') || e.direction === 2) {
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
