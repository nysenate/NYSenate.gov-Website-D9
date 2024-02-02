/**
 * @file
 * Behaviors for the Page not found.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Page Not Found behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.pagenotfound = {
    attach: function attach(context) {
      if (context !== document) {
        return;
      }

      var path404 = $(location).attr('pathname');
      var elemPath404 = $('#path404');
      $(function () {
        elemPath404.html(path404);
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=page-not-found.js.map
