/**
 * @file
 * Behaviors for the Quick Facts.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  let carouselAnimating = false;

  /**
   * Setup and attach the Quick Facts behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.quickFacts = {
    attach: function (context) {
      if (context !== document) {
        return;
      }
      const self = this;
      const carouselNavBtn = $(
        '.c-carousel--quick-facts.c-carousel--nav .c-carousel--btn',
        context
      );
      const theViewportWidth = $(window).width();

      self.highlightUpTo();

      $('#js-carousel-about-stats')
        .hammer()
        .on('swipe', function (event) {
          self.carouselAdvance(event, self, $(this));
        });

      carouselNavBtn.on(
        'click',
        Drupal.debounce(function (event) {
          self.carouselAdvance(event, self, $(this));
        }, 300)
      );

      $('.c-senate-quick-facts__button').click(function (event) {

        const tabNumber = `#panel${$(this).data('tab')}`;
        const pageBody = $('html, body');

        $(`input[value="${tabNumber}"]`).click();

        const issuesUpdatesHeader = $('#issuesUpdatesHeader').offset();
        if (issuesUpdatesHeader !== undefined) {
          const headingCurrentPosition = issuesUpdatesHeader.top;
          if (theViewportWidth > 769) {
            pageBody.animate(
              { scrollTop: headingCurrentPosition - 220 },
              '1000',
              'swing'
            );
          }
          else {
            $(tabNumber).click();
            pageBody.animate(
              { scrollTop: headingCurrentPosition - 110 },
              '1000',
              'swing'
            );
          }
        }
      });
    },
    carouselAdvance: function (e, self, item) {
      if (carouselAnimating) {
        return;
      }

      let nav;
      let newPos;
      let activeElem;

      // the nav is a different relationship if we're touch
      if (e.direction === 4 || e.direction === 2) {
        nav = $(e.target).parents('.js-carousel').siblings('.c-carousel--nav');
        // this happens on the tour carousel has different DOM
        if (nav.attr('class') === undefined) {
          nav = $(e.target)
            .parents('.c-tour--carousel-wrap')
            .siblings('.c-carousel--nav');
        }
      }
      else {
        nav = item.parent('.c-carousel--nav');
      }

      const wrap = nav.parent();
      const carousel = wrap.find('.js-carousel');
      const itemAmt = carousel.children().length;
      const itemWidth = carousel.width() / itemAmt;
      let carouselPos = 0;
      carouselPos = parseInt(carousel.css('left'));

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

      const setCarouselAnimating = function () {
        carouselAnimating = false;
      };

      // logic to set directionaltiy and left offset of carousel
      if (item.hasClass('prev') || e.direction === 4) {
        newPos = carouselPos + itemWidth;
        activeElem = Math.abs(carouselPos) / itemWidth - 1;

        self.checkCarouselBtns(nav, activeElem, itemAmt);
      }
      else if (item.hasClass('next') || e.direction === 2) {
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
    },
    highlightUpTo: function () {
      $('.c-stats--container').each(function () {
        const item = $(this).find('.c-stats--item');
        const highlight = $(this).find('.c-stats--highlight');

        if ($(window).innerWidth() > 760 && $(this).hasClass('with-hover')) {
          $(item).on('mouseenter', function () {
            const elem = $(this).children('.c-stat--illus');

            if (elem.hasClass('c-illus__signed')) {
              highlight.removeClass('highlight-second highlight-third');
              highlight.addClass('highlight-first');
            }
            else if (elem.hasClass('c-illus__waiting')) {
              highlight.removeClass('highlight-first highlight-third');
              highlight.addClass('highlight-second');
            }
            else if (elem.hasClass('c-illus__vetoed')) {
              highlight.removeClass('highlight-first highlight-second');
              highlight.addClass('highlight-third');
            }
          });
        }
      });
    }
  };
})(document, Drupal, jQuery);
