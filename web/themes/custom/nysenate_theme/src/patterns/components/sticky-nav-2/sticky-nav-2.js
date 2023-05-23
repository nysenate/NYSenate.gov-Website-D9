/**
 * @file
 * Behaviors for the Sticky Nav.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Sticky Nav behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.stickyNav2 = {
    attach: function () {
      const aboutPageNav = $('.c-about--nav');
      const adminToolbar = $('#toolbar-bar');

      if (adminToolbar.length > 0) {
        aboutPageNav.css('top', '310px');
      }
    }
  };
})(document, Drupal, jQuery);
