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
      const landingHeroContainer = $('.landing-page-hero');
      let pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
      let pagePadding = ($('main').innerWidth() - $('main').width()) / 2;

      if (heroContainer.length > 0) {
        self.heroMargin(heroContainer, pageMargin, pagePadding);
      }
      else if (landingHeroContainer.length > 0) {
        self.heroMargin(landingHeroContainer, pageMargin, pagePadding);
      }

      $(window).on('resize', function () {
        pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
        pagePadding = ($('main').innerWidth() - $('main').width()) / 2;

        if (heroContainer.length > 0) {
          self.heroMargin(heroContainer, pageMargin, pagePadding);
        }
        else if (landingHeroContainer.length > 0) {
          self.heroMargin(landingHeroContainer, pageMargin, pagePadding);
        }
      });
    },
    heroMargin: function (heroContainer, pageMargin, pagePadding) {
      heroContainer.closest('main').css('padding-top', '0');

      if ($(window).width() >= 1500) {
        heroContainer.css('margin-left', `-${(1500 / 4) - 5 }px`);
      }
      else {
        heroContainer.css('margin-left', `-${pageMargin + pagePadding}px`);
      }
    }
  };
})(document, Drupal, jQuery);
