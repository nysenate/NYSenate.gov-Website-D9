/**
 * @file
 * Behaviors for the Sidebar.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Sidebar behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.sidebar = {
    attach: function attach(context) {
      var $this = this;
      var sidebarToggle = $('.sidebar-toggle', context);
      var header = $('#js-sticky--dashboard');
      sidebarToggle.once('sidebarToggle').each($this.sidebarToggleInit);
      $(window).resize($this.debounce(function () {
        return $this.onResize(sidebarToggle);
      }));
      $this.onResize(header);
    },
    onResize: function onResize(header) {
      try {
        var headerBottom = (header.hasClass('fixed') ? parseInt(header.css('top'), 10) : header.offset().top) + header.height();
        var sidebar = $('.sidebar');
        sidebar.css('--top', "".concat(headerBottom, "px"));
      } catch (err) {
        return err;
      }
    },
    debounce: function debounce(func) {
      var _this = this;

      var timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 300;
      var timer;
      return function () {
        for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
          args[_key] = arguments[_key];
        }

        clearTimeout(timer);
        timer = setTimeout(function () {
          func.apply(_this, args);
        }, timeout);
      };
    },
    sidebarToggleInit: function sidebarToggleInit() {
      var sidebarToggle = $(this);
      sidebarToggle.click(function (e) {
        e.stopImmediatePropagation();
        var sidebar = $('.sidebar');
        var body = $('body');

        if (sidebar.hasClass('show')) {
          sidebar.removeClass('show');
          body.removeClass('sidebar-open');
          $(this).removeClass('show');
        } else {
          sidebar.addClass('show');
          body.addClass('sidebar-open');
          $(this).addClass('show');
        }
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=sidebar.es6.js.map
