!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateHeader = {
    attach: function (context) {
      let self = this;
      let userScroll = 0;
      let currentTop = $(window).scrollTop();
      let previousTop = 0;
      let nav;
      const origNav = $('#js-sticky', context);

      nav = origNav.clone().attr('id', 'js-sticky--clone').addClass('fixed');

      const menu = nav.find('.c-nav--wrap', context);
      const headerBar = nav.find('.c-header-bar', context);
      const actionBar = nav.find('.c-actionbar', context);
      const mobileNavToggle = nav.find('.js-mobile-nav--btn');
      const searchToggle = nav.find('.js-search--toggle', context);

      const $adminToolbar = $('#toolbar-bar');

      let adminHeight = $adminToolbar.length > 0 ? $adminToolbar.outerHeight() : 0;
      let headerHeight = nav.length > 0 ? nav.outerHeight() : 0; // Create empty variables to use later for calculating heights.

      let $combinedHeights = 0;

      origNav.css('visibility', 'hidden');

      // for patternlab only, can be removed once for integration
      if ($('.pl-js-pattern-example').length > 0) {
        const forTesting = '<div></div>';
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
      }
      else if ($('.layout-container').length > 0) {
        adminHeight = $adminToolbar.length > 0 ? $adminToolbar.outerHeight() : 0;
        headerHeight = nav.length > 0 ? nav.outerHeight() : 0;

        $combinedHeights = headerHeight + adminHeight;

        nav.prependTo('.layout-container').css({
          'z-index': '100',
          'margin-top': ''.concat($combinedHeights, 'px')
        });
      }

      mobileNavToggle.on('click touch', function() {
        self.toggleMobileNav(nav);
      });

      searchToggle.on('click touch', function() {
        self.toggleSearchBar(menu);
      });

      $(window).scroll(function () {
        currentTop = $(this).scrollTop();

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
            nav.removeClass('l-header__collapsed');
          }
        }
        else {
          if (
            self.isMovingDown(currentTop, previousTop) &&
            currentTop >= nav.outerHeight()
          ) {
            menu.addClass('closed');
            actionBar.removeClass('hidden');
            headerBar.addClass('collapsed');
            nav.addClass('l-header__collapsed');
            self.checkTopBarState(currentTop, previousTop, headerBar, nav);
          }
          else if (
            self.isMovingUp(currentTop, previousTop) &&
            currentTop < nav.outerHeight()
          ) {
            menu.removeClass('closed');
            actionBar.addClass('hidden');
            headerBar.removeClass('collapsed');
            nav.removeClass('l-header__collapsed');
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

      if (currentTop > nav.outerHeight() && !headerBar.hasClass('collapsed') && !nav.hasClass('l-header__collapsed')) {
        headerBar.addClass('collapsed');
        nav.addClass('l-header__collapsed');
      }
      else if (
        currentTop <= nav.outerHeight() &&
        headerBar.hasClass('collapsed') &&
        nav.hasClass('l-header__collapsed')
      ) {
        headerBar.removeClass('collapsed');
        nav.removeClass('l-header__collapsed');
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
    },
    toggleSearchBar: function(menu) {
      if(menu.hasClass('search-open')) {
        menu.removeClass('search-open');
        menu.find('.c-site-search--box').blur();
        $('.c-site-search').removeClass('open');
        $('.c-site-search').blur();
      }
      else {
        menu.addClass('search-open');
        menu.find('.c-site-search--box').focus();
        $('.c-site-search').addClass('open');
        $('.c-site-search').find('.c-site-search--box').focus();
      }
    },
    closeSearch: function() {

      if($('.c-nav--wrap').hasClass('search-open')) {
        $('.c-nav--wrap').removeClass('search-open');
        $('.c-nav--wrap').find('.c-site-search--box').blur();
      }
    },
    isHomepage: function() {
      return $('.view-homepage-hero').length > 0;
    },
    isInSession: function() {
      return this.isHomepage() && $('.c-hero-livestream-video').length > 0;
    },
  };
})(document, Drupal, jQuery);
