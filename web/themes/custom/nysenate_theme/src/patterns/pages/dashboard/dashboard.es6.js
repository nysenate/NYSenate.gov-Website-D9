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

        const sidebarToggle = nav.find('.sidebar-toggle');
        sidebarToggle.each(Drupal.behaviors.sidebar.sidebarToggleInit);

        // place clone
        nav.prependTo('.page').css({
          'z-index': '100'
        });

        self.alignPosition(origNav, nav);

        win.scroll(
          Drupal.debounce(() => self.checkTopBarState(nav, headerBar), 300)
        );

        self.initToolbarObserver(origNav, nav, self.alignPosition);
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
    },
    alignPosition: function (orig, clone) {
      try {
        const origTop = orig.position().top;
        clone.css('top', `${typeof origTop === 'number' ? origTop : 0}px`);
      }
      catch (err) {
        return err;
      }
    },
    initToolbarObserver: function (origNav, nav, alignPosition) {
      // Select the node that will be observed for mutations
      const targetNode = $('body');

      // Options for the observer (which mutations to observe)
      const config = { attributes: true, childList: true, subtree: true };

      // Callback function to execute when mutations are observed
      const callback = (mutationList) => {
        for (const mutation of mutationList) {
          if (
            mutation.attributeName === 'style' &&
            mutation.target.localName === 'body'
          ) {
            alignPosition(origNav, nav);
          }
        }
      };

      // Create an observer instance linked to the callback function
      const observer = new MutationObserver(callback);

      try {
        // Start observing the target node for configured mutations
        targetNode.each(function () {
          observer.observe(this, config);
        });
      }
      catch (err) {
        observer.disconnect();
        return err;
      }
    }
  };
})(document, Drupal, jQuery);
