!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysenateHeader = {
    attach: function attach(context) {
      var self = this;
      var userScroll = 0;
      var currentTop = $(window).scrollTop();
      var previousTop = 0;
      var nav;
      var origNav = $('#js-sticky', context);
      nav = origNav.clone().attr('id', 'js-sticky--clone').addClass('fixed');
      var menu = nav.find('.c-nav--wrap', context);
      var headerBar = nav.find('.c-header-bar', context);
      var actionBar = nav.find('.c-actionbar', context);
      var mobileNavToggle = nav.find('.js-mobile-nav--btn');
      var searchToggle = nav.find('.js-search--toggle', context);
      var $adminToolbar = $('#toolbar-bar');
      var adminHeight = $adminToolbar.length > 0 ? $adminToolbar.outerHeight() : 0;
      var headerHeight = nav.length > 0 ? nav.outerHeight() : 0; // Create empty variables to use later for calculating heights.

      var $combinedHeights = 0;
      origNav.css('visibility', 'hidden'); // for patternlab only, can be removed once for integration

      if ($('.pl-js-pattern-example').length > 0) {
        var forTesting = '<div></div>';
        $('.pl-js-pattern-example').css('position', 'relative');
        nav.css('position', 'absolute');
        nav.prependTo('.pl-js-pattern-example').css({
          'z-index': '100'
        });

        if (nav.length > 0) {
          $(forTesting).appendTo('.pl-js-pattern-example').css({
            'background': 'gray',
            'height': '400px'
          });
        }
      } else if ($('.layout-container').length > 0) {
        adminHeight = $adminToolbar.length > 0 ? $adminToolbar.outerHeight() : 0;
        headerHeight = nav.length > 0 ? nav.outerHeight() : 0;
        $combinedHeights = headerHeight + adminHeight;
        nav.prependTo('.layout-container').css({
          'z-index': '100',
          'margin-top': ''.concat($combinedHeights, 'px')
        });
      }

      mobileNavToggle.on('click touch', function () {
        self.toggleMobileNav(nav);
      });
      searchToggle.on('click touch', function () {
        self.toggleSearchBar(menu);
      });
      $(window).scroll(function () {
        currentTop = $(this).scrollTop(); // Close the nav after scrolling 1/3rd of page.

        if (Math.abs(userScroll - $(window).scrollTop()) > $(window).height() / 3) {
          if ($('.c-nav--wrap').hasClass('search-open')) {
            $('.c-nav--wrap').removeClass('search-open');
            $('.c-nav--wrap').find('.c-site-search--box').blur();
          }
        }

        if ($(window).width() < 760) {
          if (self.isMovingDown(currentTop, previousTop) && currentTop >= nav.outerHeight()) {
            actionBar.removeClass('hidden');
            self.checkTopBarState(currentTop, previousTop, headerBar, nav);
          } else if (self.isMovingUp(currentTop, previousTop) && currentTop < nav.outerHeight()) {
            actionBar.addClass('hidden');
            headerBar.removeClass('collapsed');
          }
        } else {
          if (self.isMovingDown(currentTop, previousTop) && currentTop >= nav.outerHeight()) {
            menu.addClass('closed');
            actionBar.removeClass('hidden');
            headerBar.addClass('collapsed');
            self.checkTopBarState(currentTop, previousTop, headerBar, nav);
          } else if (self.isMovingUp(currentTop, previousTop) && currentTop < nav.outerHeight()) {
            menu.removeClass('closed');
            actionBar.addClass('hidden');
            headerBar.removeClass('collapsed');
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
        $('.c-site-search').removeClass('open');
        $('.c-site-search').blur();
      } else {
        menu.addClass('search-open');
        menu.find('.c-site-search--box').focus();
        $('.c-site-search').addClass('open');
        $('.c-site-search').find('.c-site-search--box').focus();
      }
    },
    closeSearch: function closeSearch() {
      if ($('.c-nav--wrap').hasClass('search-open')) {
        $('.c-nav--wrap').removeClass('search-open');
        $('.c-nav--wrap').find('.c-site-search--box').blur();
      }
    },
    isHomepage: function isHomepage() {
      return $('.view-homepage-hero').length > 0;
    },
    isInSession: function isInSession() {
      return this.isHomepage() && $('.c-hero-livestream-video').length > 0;
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-header.js.map
