/**
 * @file
 * Behaviors for the Hero.
 */
/* eslint-disable max-len */
!((document, Drupal, $) => {
  'use strict';
  /**
   * Setup and attach the Hero behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.hero = {
    attach: function () {
      const self = this;
      const heroContainer = $('.hero--homepage');
      let pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
      let pagePadding = ($('main').innerWidth() - $('main').width()) / 2;

      self.heroMargin(heroContainer, pageMargin, pagePadding);

      $(window).on('resize', function () {
        pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
        pagePadding = ($('main').innerWidth() - $('main').width()) / 2;

        self.heroMargin(heroContainer, pageMargin, pagePadding);
      });
    },
    heroMargin: function (heroContainer, pageMargin, pagePadding) {
      if ($(window).width() >= 1500) {
        heroContainer.css('margin-left', `-${(1500 / 4) - 5 }px`);
      }
      else {
        heroContainer.css('margin-left', `-${pageMargin + pagePadding}px`);
      }
    }
  };
})(document, Drupal, jQuery);
