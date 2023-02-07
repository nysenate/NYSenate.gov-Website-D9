/**
 * @file
 * Behaviors for the Dashboard Header.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Dashboard Header behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.dashboard = {
    attach: function (context) {
      if (context !== document) {
        return;
      }

      const win = $(window);
      const origNav = $('#js-sticky--dashboard', context);
      const self = this;

      origNav.once('navigation').each(function () {
        const nav = origNav
          .clone()
          .attr('id', 'js-sticky--dashboard--clone')
          .addClass('fixed');

        const headerBar = nav.find('.c-header-bar');

        // place clone
        nav.prependTo('.page').css({
          'z-index': '100'
        });
        win.scroll(
          Drupal.debounce(() => self.checkTopBarState(nav, headerBar), 300)
        );
      });
    },

    checkTopBarState: function (nav, headerBar) {
      let doc = $(document);
      let currentTop = doc.scrollTop();

      if (currentTop > nav.outerHeight() && !headerBar.hasClass('collapsed')) {
        headerBar.addClass('collapsed');
      }
      else if (
        currentTop <= nav.outerHeight() &&
        headerBar.hasClass('collapsed')
      ) {
        headerBar.removeClass('collapsed');
      }
    }
  };
})(document, Drupal, jQuery);
