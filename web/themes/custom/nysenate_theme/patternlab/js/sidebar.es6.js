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
      sidebarToggle.once('sidebarToggle').each(function () {
        var sidebarToggle = $(this);
        sidebarToggle.click(function () {
          var sidebar = $('.sidebar');
          var body = $('body');

          if (sidebar.hasClass('show')) {
            sidebar.removeClass('show');
            body.removeClass('.sidebar-open');
            $(this).removeClass('show');
          } else {
            sidebar.addClass('show');
            body.addClass('.sidebar-open');
            $(this).addClass('show');
          }
        });
        $(window).resize($this.debounce(function () {
          return $this.onResize(sidebarToggle);
        }));
        $this.onResize(sidebarToggle);
      });
    },
    onResize: function onResize(sidebarToggle) {
      var sidebarToggleBottom = sidebarToggle.offset().top + sidebarToggle.outerHeight();
      var sidebar = $('.sidebar');
      sidebar.css('--top', "".concat(sidebarToggleBottom, "px"));
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
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=sidebar.es6.js.map
