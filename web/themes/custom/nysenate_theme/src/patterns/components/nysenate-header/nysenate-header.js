!((document, Drupal, $) => {
  'use strict';
  Drupal.behaviors.nysenateHeader = {
    attach: function (context) {
      let self = this;

      // If admin toolbar is present and the user is not on mobile,
      // abort and use static nav bar.
      if ($('#toolbar-administration').length > 0 && !self.isMobileWidth()) {
        let searchToggle = $('.js-search--toggle');
        $(once('nySenateHeader', searchToggle)).on('click touch', function (e) {
          self.toggleSearchBar(0, e);
        });
        return;
      }

      let userScroll = 0;
      let currentTop = $(window).scrollTop();
      let previousTop = 0;
      let nav;
      let actionBar;
      let origActionBar;
      const origNav = $('#js-sticky', context);

      nav = origNav.clone().attr('id', 'js-sticky--clone').addClass('fixed');

      let menu;
      let headerBar;
      let mobileNavToggle;
      let topbarDropdown;

      const $adminToolbar = $('#toolbar-bar');
      const $adminTray = $('#toolbar-item-administration-tray.toolbar-tray');

      if ($adminToolbar.length > 0) {
        const observer = new MutationObserver(function (mutations) {
          mutations.forEach(function () {
            nav.prependTo('.layout-container').css({
              'z-index': '100',
              'margin-top': $('body').css('padding-top')
            });
          });
        });

        observer.observe($adminTray[0], {
          attributes: true,
          attributeFilter: ['class']
        });
      }

      if (self.isSenatorLanding(origNav)) {
        actionBar = nav.find('.c-senator-hero');
        nav.find('#senatorImage').html(nav.find('#smallShotImage').html());

        if (self.isSenatorCollapsed()) {
          // place origin Nav
          origNav
            .prependTo('.page')
            .css({
              'z-index': '100'
            })
            .addClass('l-header__collapsed')
            .css('visibility', 'hidden');

          nav
            .prependTo('.page')
            .css({
              'z-index': '100'
            })
            .addClass('l-header__collapsed');

          menu = nav.find('.c-nav--wrap');
          headerBar = nav.find('.c-header-bar');
          mobileNavToggle = nav.find('.js-mobile-nav--btn');
          topbarDropdown = nav.find('.c-login--list');

          // bind scrolling
          $(window).scroll(function () {
            currentTop = $(this).scrollTop();

            self.senatorLandingScroll(
              currentTop,
              previousTop,
              userScroll,
              origNav,
              menu,
              headerBar,
              nav,
              actionBar
            );

            previousTop = $(document).scrollTop();
          });
        }
        else {
          // place clone
          nav
            .prependTo('.page')
            .css({
              'z-index': '14'
            })
            .addClass('l-header__collapsed');

          menu = nav.find('.c-nav--wrap');
          headerBar = nav.find('.c-header-bar');
          mobileNavToggle = nav.find('.js-mobile-nav--btn');
          topbarDropdown = nav.find('.c-login--list');

          // collapse / hide nav
          menu.addClass('closed');
          actionBar.addClass('hidden');

          // set headerBar to collapsed state
          origNav.find('.c-header-bar').css('visibility', 'hidden');

          // bind scrolling
          $(window).scroll(function () {
            currentTop = $(this).scrollTop();

            self.senatorLandingScroll(
              currentTop,
              previousTop,
              userScroll,
              origNav,
              menu,
              headerBar,
              nav,
              actionBar
            );

            previousTop = $(document).scrollTop();
          });
        }
      }
      else if (self.isHomepage()) {
        // place clone
        nav.prependTo('.page').css({
          'z-index': '100'
        });

        origActionBar = $('.c-actionbar').first();
        actionBar = origActionBar.clone();
        actionBar.appendTo(nav).addClass('hidden');

        menu = nav.find('.c-nav--wrap');
        headerBar = nav.find('.c-header-bar');
        mobileNavToggle = nav.find('.js-mobile-nav--btn');
        topbarDropdown = nav.find('.c-login--list');

        // hide original nav -- just for visual
        origNav.css('visibility', 'hidden');

        if (self.isTooLow(currentTop)) {
          menu.addClass('closed');
        }

        if (self.isMicroSitePage()) {
          $(window).scroll(function () {
            currentTop = $(this).scrollTop();
            self.microSiteScroll(currentTop, previousTop, headerBar, nav, menu);
            previousTop = $(document).scrollTop();
          });
        }
        else {
          $(window).scroll(function () {
            currentTop = $(this).scrollTop();
            self.basicScroll(
              origNav,
              currentTop,
              previousTop,
              headerBar,
              nav,
              menu,
              actionBar,
              origActionBar,
              'hide-actionbar'
            );
            previousTop = $(document).scrollTop();
          });
        }
      }
      else if (self.isErrorPage()) {
        nav.css('display', 'none');
        return false;
      }
      else {
        // place clone
        nav.prependTo('.page').css({
          'z-index': '100'
        });

        if (self.isOpenData() && self.isIssuePage()) {
          origActionBar = nav.find('.c-actionbar');
          actionBar = origActionBar.clone();

          actionBar.appendTo(nav);
          origActionBar.css('visibility', 'hidden');
          actionBar.appendTo(nav).removeClass('hidden');
        }

        if (self.isIssuePage()) {
          origNav.find('.c-actionbar').removeClass('hidden');
          origNav.find('.c-actionbar').css('visibility', '');
        }

        menu = nav.find('.c-nav--wrap');
        headerBar = nav.find('.c-header-bar');
        mobileNavToggle = nav.find('.js-mobile-nav--btn');
        topbarDropdown = nav.find('.c-login--list');

        // hide original nav -- just for visual
        origNav.css('visibility', 'hidden');

        if (self.isTooLow(currentTop)) {
          menu.addClass('closed');
        }

        if (self.isMicroSitePage()) {
          $(window).scroll(function () {
            currentTop = $(this).scrollTop();

            self.microSiteScroll(currentTop, previousTop, headerBar, nav, menu);

            previousTop = $(document).scrollTop();
          });
        }
        else {
          $(window).scroll(function () {
            currentTop = $(this).scrollTop();

            self.basicScroll(
              origNav,
              currentTop,
              previousTop,
              headerBar,
              nav,
              menu,
              actionBar,
              origActionBar,
              self.isOpenData() && self.isIssuePage()
                ? 'show-actionbar'
                : 'hide-action-bar',
              self.isOpenData() && self.isIssuePage()
            );

            previousTop = $(document).scrollTop();
          });
        }
      }

      $(once('nySenateHeaderMobile', mobileNavToggle))
      .on('click touch', function () {
        self.toggleMobileNav(menu);
      });

      let searchToggle = $('.js-search--toggle');
      $(once('nySenateHeader', searchToggle)).on('click touch', function (e) {
        self.toggleSearchBar(userScroll, e);
      });

      $(window).on('load', function() {
        self.moveMessage();
      });
    },
    microSiteScroll: function (currentTop, previousTop, headerBar, nav, menu) {
      this.checkTopBarState(currentTop, previousTop, headerBar, nav);
      this.checkMenuState(menu, currentTop, previousTop);
    },
    basicScroll: function (
      origNav,
      currentTop,
      previousTop,
      headerBar,
      nav,
      menu,
      actionBar,
      origActionBar,
      toggleActionBar,
      topBarToggle = false
    ) {
      if (origActionBar) {
        if (
          this.isMovingDown(currentTop, previousTop) &&
          currentTop + nav.outerHeight() >= origActionBar.offset().top
        ) {
          actionBar.removeClass('hidden');
          origActionBar.addClass('hidden');
        }
        else if (
          this.isMovingUp(currentTop, previousTop) &&
          currentTop <= origActionBar.offset().top
        ) {
          if (toggleActionBar !== 'show-actionbar') {
            actionBar.addClass('hidden');
            origActionBar.removeClass('hidden');
          }
        }
      }

      this.checkTopBarState(
        currentTop,
        previousTop,
        headerBar,
        nav,
        topBarToggle
      );
      this.checkMenuState(menu, currentTop, previousTop, topBarToggle);
    },
    senatorLandingScroll: function (
      currentTop,
      previousTop,
      userScroll,
      origNav,
      menu,
      headerBar,
      nav,
      actionBar
    ) {
      // Close the nav after scrolling 1/3rd of page.
      if (
        Math.abs(userScroll - $(window).scrollTop()) >
        $(window).height() / 3
      ) {
        this.closeSearch();
      }

      var menuHeigth;
      if (menu.length > 0) {
        menuHeigth = menu.outerHeight();
      }
      else {
        menuHeigth = 0;
      }

      var heroHeight =
        origNav.outerHeight() -
        menuHeigth -
        $('.c-senator-hero--contact-btn').outerHeight() -
        headerBar.outerHeight() -
        nav.outerHeight();

      if ($(window).width() < 769) {
        if (
          this.isMovingDown(currentTop, previousTop) &&
          currentTop >= origNav.outerHeight() &&
          !this.isSenatorCollapsed()
        ) {
          actionBar.removeClass('hidden');
          this.checkTopBarState(currentTop, previousTop, headerBar, nav);
        }
        else if (
          this.isMovingUp(currentTop, previousTop) &&
          currentTop < origNav.outerHeight() &&
          !this.isSenatorCollapsed()
        ) {
          actionBar.addClass('hidden');
          headerBar.removeClass('collapsed');
        }
      }
      else {
        this.checkTopBarState(currentTop, previousTop, headerBar, nav);

        if (
          this.isMovingUp(currentTop, previousTop) &&
          currentTop <= origNav.outerHeight() - 100 - 100
        ) {
          if (this.isSenatorCollapsed()) {
            menu.removeClass('closed');
          }
          else {
            menu.addClass('closed');
          }
          if (
            this.isMovingUp(currentTop, previousTop) &&
            currentTop <= origNav.outerHeight() - 100 - 100 - 40 - 100
          ) {
            actionBar.addClass('hidden');
            $('#largeHeadshot').addClass('hidden');
            $('#smallHeadshot').removeClass('hidden');
          }
        }
        else if (currentTop >= heroHeight) {
          actionBar.removeClass('hidden');
          headerBar.addClass('collapsed');
          this.checkMenuState(menu, currentTop, previousTop);
        }
      }
    },
    checkMenuState: function (
      menu,
      currentTop,
      previousTop,
      topBarToggle = false
    ) {
      if (this.isOutOfBounds(currentTop, previousTop)) {
        return;
      }

      if (!topBarToggle) {
        if (this.isMovingDown(currentTop, previousTop)) {
          menu.addClass('closed');
        }
        else if (this.isMovingUp(currentTop, previousTop)) {
          menu.removeClass('closed');
        }
      }
    },
    isMovingUp: function (currentTop, previousTop) {
      return currentTop < previousTop;
    },
    isMovingDown: function (currentTop, previousTop) {
      return currentTop > previousTop;
    },
    checkTopBarState: function (
      currentTop,
      previousTop,
      headerBar,
      nav,
      topBarToggle = false
    ) {
      if (this.isOutOfBounds(currentTop, previousTop)) {
        return;
      }

      if (!topBarToggle) {
        if (
          currentTop > nav.outerHeight()
        ) {
          headerBar.addClass('collapsed');
        }
        else if (
          currentTop <= nav.outerHeight()
        ) {
          headerBar.removeClass('collapsed');
        }
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
    toggleMobileNav: function (menu) {
      var body = $('body');

      // toggle classes
      body.toggleClass('nav-open');
      menu.removeClass('closed');
    },
    toggleSearchBar: function (userScroll, e) {
      e.preventDefault();
      const button = e.target;
      // Set page position, to detect scrolling later.
      userScroll = $(window).scrollTop();

      var nav = $(button).parents('.c-nav--wrap');

      if (nav.hasClass('search-open')) {
        nav.removeClass('search-open');
        nav.find('.c-site-search--box').blur();
        $('.c-site-search').removeClass('open');
        $('.c-site-search').blur();
      }
      else {
        nav.addClass('search-open');
        nav.find('.c-site-search--box').focus();
        $('.c-site-search').addClass('open');
        $('.c-site-search').find('.c-site-search--box').focus();
      }
    },
    closeSearch: function () {
      if ($('.c-nav--wrap').hasClass('search-open')) {
        $('.c-nav--wrap').removeClass('search-open');
        $('.c-nav--wrap').find('.c-site-search--box').blur();
      }
    },
    isHomepage: function () {
      return $('.hero--homepage').length > 0;
    },
    isInSession: function () {
      return this.isHomepage() && $('.c-hero-livestream-video').length > 0;
    },
    isMicroSitePage: function () {
      return $('.hero--senator').length > 0;
    },
    isSenatorLanding: function (origNav) {
      return this.isMicroSitePage() && !origNav.hasClass('l-header__collapsed');
    },
    isOpenData: function () {
      return $('.open-data-section').length > 0;
    },
    isIssuePage: function () {
      return $('.page--issues').length > 0;
    },
    isSenatorCollapsed: function () {
      return $('.hero--senator-collapsed').length > 0;
    },
    isErrorPage: function () {
      return $('.error-page-header').length > 0;
    },
    moveMessage: function() {
      const statusMessage = $('.message').parent();
      const blockTabs = $('#block-tabs');
      if (statusMessage && blockTabs) {
        blockTabs.after(statusMessage);
      }
    },
    isMobileWidth: function () {
      return (window.innerWidth <= 1024);
    }
  };
})(document, Drupal, jQuery);
