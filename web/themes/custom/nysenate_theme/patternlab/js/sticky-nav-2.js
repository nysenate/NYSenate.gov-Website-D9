/**
 * @file
 * Behaviors for the Sticky Nav.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Sticky Nav behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.stickyNav2 = {
    attach: function attach() {
      var aboutPageNav = $('.c-about--nav');
      var adminToolbar = $('#toolbar-bar');
      var adminTray = $('#toolbar-item-administration-tray.toolbar-tray');

      if (adminToolbar.length > 0) {
        aboutPageNav.css('top', "".concat(270 + adminToolbar.height() + adminTray.height(), "px"));
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=sticky-nav-2.js.map
