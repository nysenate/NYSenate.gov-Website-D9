!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysenateHeader = {
    attach: function attach(context) {
      var self = this;
      var userScroll = 0;
      var currentTop = $(document).scrollTop();
      var previousTop = 0;
      var nav = $('#js-sticky', context);
      var menu = nav.find('.c-nav--wrap', context);
      var headerBar = nav.find('.c-header-bar', context);
      var actionBar = $('.c-actionbar', context);
      var mobileNavToggle = nav.find('.js-mobile-nav--btn');
      var searchToggle = $('.js-search--toggle', context);
      mobileNavToggle.on('click', function () {
        self.toggleMobileNav(nav);
      });
      searchToggle.on('click', function () {
        self.toggleSearchBar(menu);
      });
      $(window).scroll(function () {
        // Close the nav after scrolling 1/3rd of page.
        if (Math.abs(userScroll - $(window).scrollTop()) > $(window).height() / 3) {
          if ($('.c-nav--wrap').hasClass('search-open')) {
            $('.c-nav--wrap').removeClass('search-open');
            $('.c-nav--wrap').find('.c-site-search--box').blur();
          }
        }

        var heroHeight = nav.outerHeight() - menu.outerHeight() - headerBar.outerHeight() - nav.outerHeight();

        if ($(window).width() < 760) {
          if (self.isMovingDown(currentTop, previousTop) && currentTop >= nav.outerHeight()) {
            actionBar.removeClass('hidden');
            self.checkTopBarState(currentTop, previousTop, headerBar, nav);
          } else if (self.isMovingUp(currentTop, previousTop) && currentTop < nav.outerHeight()) {
            actionBar.addClass('hidden');
            headerBar.removeClass('collapsed');
          }
        } else {
          self.checkTopBarState(currentTop, previousTop, headerBar, nav);

          if (self.isMovingUp(currentTop, previousTop) && currentTop <= nav.outerHeight() - 100 - 100) {
            menu.addClass('closed');
            headerBar.addClass('collapsed');

            if (self.isMovingUp(currentTop, previousTop) && currentTop <= nav.outerHeight() - 100 - 100 - 40 - 100) {
              actionBar.addClass('hidden');
            }
          } else if (currentTop >= heroHeight) {
            actionBar.removeClass('hidden');
            self.checkMenuState(menu);
          }
        }

        previousTop = $(document).scrollTop();
      });
    },
    checkMenuState: function checkMenuState(menu) {
      if (this.isOutOfBounds()) {
        return;
      }

      if (this.isMovingDown()) {
        menu.addClass('closed');
      } else if (this.isMovingUp()) {
        menu.removeClass('closed');
      }
    },
    isMovingUp: function isMovingUp(currentTop, previousTop) {
      return currentTop < previousTop;
    },
    isMovingDown: function isMovingDown(currentTop, previousTop) {
      return currentTop > previousTop;
    },
    checkTopBarState: function checkTopBarState(currentTop, previousTop, headerBar, nav) {
      if (this.isOutOfBounds(currentTop, previousTop)) {
        return;
      }

      if (currentTop > nav.outerHeight() && !headerBar.hasClass('collapsed')) {
        headerBar.addClass('collapsed');
      } else if (currentTop <= nav.outerHeight() && headerBar.hasClass('collapsed')) {
        headerBar.removeClass('collapsed');
      }
    },
    isOutOfBounds: function isOutOfBounds(currentTop, previousTop) {
      return this.isTooHigh(currentTop, previousTop) || this.isTooLow(currentTop);
    },
    isTooHigh: function isTooHigh(currentTop, previousTop) {
      return currentTop < 0 || previousTop < 0;
    },
    isTooLow: function isTooLow(currentTop) {
      return currentTop + $(window).height() >= $(document).height();
    },
    toggleMobileNav: function toggleMobileNav() {
      var body = $('body'); // toggle classes

      body.toggleClass('nav-open');
    },
    toggleSearchBar: function toggleSearchBar(menu) {
      if (menu.hasClass('search-open')) {
        menu.removeClass('search-open');
        menu.find('.c-site-search--box').blur();
      } else {
        menu.addClass('search-open');
        menu.find('.c-site-search--box').focus();
      }
    },
    closeSearch: function closeSearch() {
      if ($('.c-nav--wrap').hasClass('search-open')) {
        $('.c-nav--wrap').removeClass('search-open');
        $('.c-nav--wrap').find('.c-site-search--box').blur();
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-header.js.map
