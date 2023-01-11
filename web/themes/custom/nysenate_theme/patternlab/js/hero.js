/**
 * @file
 * Behaviors for the Hero.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Hero behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.hero = {
    attach: function attach() {
      var self = this;
      var heroContainer = $('.hero--homepage');
      var pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
      var pagePadding = ($('main').innerWidth() - $('main').width()) / 2;
      self.heroMargin(heroContainer, pageMargin, pagePadding);
      $(window).on('resize', function () {
        pageMargin = ($('main').outerWidth(true) - $('main').outerWidth()) / 2;
        pagePadding = ($('main').innerWidth() - $('main').width()) / 2;
        self.heroMargin(heroContainer, pageMargin, pagePadding);
      });
    },
    heroMargin: function heroMargin(heroContainer, pageMargin, pagePadding) {
      if ($(window).width() >= 1500) {
        heroContainer.css('margin-left', "-".concat(1500 / 4 - 5, "px"));
      } else {
        heroContainer.css('margin-left', "-".concat(pageMargin + pagePadding, "px"));
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=hero.js.map
