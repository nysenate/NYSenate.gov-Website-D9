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
      const $this = this;
      const sidebarToggle = $('.sidebar-toggle', context);
      const header = $('#js-sticky--dashboard');

      $(once('sidebarToggle', sidebarToggle)).each($this.sidebarToggleInit);
      $(window).resize($this.debounce(() => $this.onResize(sidebarToggle)));
      $this.onResize(header);
    },
    onResize: function (header) {
      try {
        const headerBottom =
          (header.hasClass('fixed')
            ? parseInt(header.css('top'), 10)
            : header.offset().top) + header.height();
        const sidebar = $('.sidebar');
        sidebar.css('--top', `${headerBottom}px`);
      }
      catch (err) {
        return err;
      }
    },
    debounce: function (func, timeout = 300) {
      let timer;
      return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          func.apply(this, args);
        }, timeout);
      };
    },
    sidebarToggleInit: function () {
      const sidebarToggle = $(this);

      sidebarToggle.click(function (e) {
        e.stopImmediatePropagation();

        const sidebar = $('.sidebar');
        const body = $('body');

        if (sidebar.hasClass('show')) {
          sidebar.removeClass('show');
          body.removeClass('sidebar-open');
          $(this).removeClass('show');
        }
        else {
          sidebar.addClass('show');
          body.addClass('sidebar-open');
          $(this).addClass('show');
        }
      });
    }
  };
})(document, Drupal, jQuery);
