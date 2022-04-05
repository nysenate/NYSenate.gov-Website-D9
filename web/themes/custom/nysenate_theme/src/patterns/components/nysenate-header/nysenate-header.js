!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateHeader = {
    attach: function (context) {
      let self = this;
      let userScroll = 0;
      let currentTop = $(document).scrollTop();
      let previousTop = 0;
      const nav = $('#js-sticky', context);
      const menu = nav.find('.c-nav--wrap', context);
      const headerBar = nav.find('.c-header-bar', context);
      const actionBar = $('.c-actionbar', context);
      const mobileNavToggle = nav.find('.js-mobile-nav--btn');

      mobileNavToggle.on('click', function() {
        self.toggleMobileNav(nav);
      });

      $(window).scroll(function () {
        // Close the nav after scrolling 1/3rd of page.
        if (
          Math.abs(userScroll - $(window).scrollTop()) >
          $(window).height() / 3
        ) {
          if ($('.c-nav--wrap').hasClass('search-open')) {
            $('.c-nav--wrap').removeClass('search-open');
            $('.c-nav--wrap').find('.c-site-search--box').blur();
          }
        }

        var heroHeight =
          nav.outerHeight() -
          menu.outerHeight() -
          headerBar.outerHeight() -
          nav.outerHeight();

        if ($(window).width() < 760) {
          if (
            self.isMovingDown(currentTop, previousTop) &&
            currentTop >= nav.outerHeight()
          ) {
            actionBar.removeClass('hidden');
            self.checkTopBarState(currentTop, previousTop, headerBar, nav);
          }
          else if (
            self.isMovingUp(currentTop, previousTop) &&
            currentTop < nav.outerHeight()
          ) {
            actionBar.addClass('hidden');
            headerBar.removeClass('collapsed');
          }
        }
        else {
          self.checkTopBarState(currentTop, previousTop, headerBar, nav);

          if (
            self.isMovingUp(currentTop, previousTop) &&
            currentTop <= nav.outerHeight() - 100 - 100
          ) {
            menu.addClass('closed');
            headerBar.addClass('collapsed');

            if (
              self.isMovingUp(currentTop, previousTop) &&
              currentTop <= nav.outerHeight() - 100 - 100 - 40 - 100
            ) {
              actionBar.addClass('hidden');
            }
          }
          else if (currentTop >= heroHeight) {
            actionBar.removeClass('hidden');
            self.checkMenuState(menu);
          }
        }

        previousTop = $(document).scrollTop();
      });
    },
    checkMenuState: function (menu) {
      if (this.isOutOfBounds()) {
        return;
      }

      if (this.isMovingDown()) {
        menu.addClass('closed');
      }
      else if (this.isMovingUp()) {
        menu.removeClass('closed');
      }
    },
    isMovingUp: function (currentTop, previousTop) {
      return currentTop < previousTop;
    },
    isMovingDown: function (currentTop, previousTop) {
      return currentTop > previousTop;
    },
    checkTopBarState: function (currentTop, previousTop, headerBar, nav) {
      if (this.isOutOfBounds(currentTop, previousTop)) {
        return;
      }

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
    isOutOfBounds: function (currentTop, previousTop) {
      return (
        this.isTooHigh(currentTop, previousTop) || this.isTooLow(currentTop)
      );
    },
    isTooHigh: function (currentTop, previousTop) {
      return currentTop < 0 || previousTop < 0;
    },
    isTooLow: function (currentTop) {
      return currentTop + $(window).height() >= $(document).height();
    },
    toggleMobileNav: function () {
      var body = $('body');

      // toggle classes
      body.toggleClass('nav-open');
    }
  };
})(document, Drupal, jQuery);
