/**
 * @file
 * Behaviors for the Quick Facts.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Quick Facts behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.quickFacts = {
    attach: function() {
      const self = this;
      let carouselAnimating = false;
      const carouselNavBtn = $('.c-carousel--nav .c-carousel--btn');

      self.highlightUpTo();

      $('#js-carousel-about-stats').hammer().on('swipe', function(event) {
        self.carouselAdvance(event, carouselAnimating, self, $(this));
      });

      carouselNavBtn.on('click', function (event) {
        self.carouselAdvance(event, carouselAnimating, self, $(this));
      });
    },
    carouselAdvance: function(e, carouselAnimating, self, item) {
      if(carouselAnimating) {
        return;
      }

      let nav;
      let newPos;
      let activeElem;

      // the nav is a different relationship if we're touch
      if(e.direction === 4 || e.direction === 2) {
        nav = $(e.target).parents('.js-carousel').siblings('.c-carousel--nav');
        // this happens on the tour carousel has different DOM
        if(nav.attr('class') === undefined) {
          nav = $(e.target).parents('.c-tour--carousel-wrap').siblings('.c-carousel--nav');
        }
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
      if (e.direction === 4 && nav.children('.prev').hasClass('hidden')) {
        return false;
      }
      // if the next button is hidden - do not move that way or at all
      else if (e.direction === 2 && nav.children('.next').hasClass('hidden')) {
        return false;
      }
      else {
        e.preventDefault();
      }

      // set flag to stop button jammers
      carouselAnimating = true;

      const setCarouselAnimating = function() {
        carouselAnimating = false;
      };

      // logic to set directionaltiy and left offset of carousel
      if(item.hasClass('prev') || e.direction === 4) {
        newPos = carouselPos + itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth - 1;

        self.checkCarouselBtns(nav, activeElem, itemAmt);
      }
      else if(item.hasClass('next')  || e.direction === 2) {
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
    checkCarouselBtns: function(nav, activeElem, itemAmt) {
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
    },
    highlightUpTo: function() {
      $('.c-stats--container').each(function () {
        const item = $(this).find('.c-stats--item');
        const highlight = $(this).find('.c-stats--highlight');

        if ($(window).innerWidth() > 760 && $(this).hasClass('with-hover')) {
          $(item).on('mouseenter', function() {
            const elem = $(this).children('.c-stat--illus');

            if (elem.hasClass('c-illus__signed')) {
              highlight.addClass('c-stats--highlight highlight-first');
            }
            else if (elem.hasClass('c-illus__waiting')) {
              highlight.addClass('c-stats--highlight highlight-second');
            }
            else if (elem.hasClass('c-illus__vetoed')) {
              highlight.addClass('c-stats--highlight highlight-third');
            }
          });
        }
      });
    },
  };
})(document, Drupal, jQuery);
