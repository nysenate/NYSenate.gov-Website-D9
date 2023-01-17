/**
 * @file
 * Behaviors for the Sidebar.
 */
/* eslint-disable max-len */

!((document, Drupal, $) => {
  'use strict';

  /**
   * Setup and attach the Sidebar behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sidebar = {
    attach: function (context) {
      $('.sidebar-toggle', context)
        .once('sidebarToggle')
        .each(function () {
          const sidebarToggle = $(this);

          sidebarToggle.click(function () {
            const sidebar = $('.sidebar');
            const body = $('body');

            if (sidebar.hasClass('show')) {
              sidebar.removeClass('show');
              body.removeClass('.sidebar-open');
              $(this).removeClass('show');
            }
            else {
              sidebar.addClass('show');
              body.addClass('.sidebar-open');
              $(this).addClass('show');
            }
          });
        });
    }
  };
})(document, Drupal, jQuery);
