!((document, Drupal) => {
  'use strict';
  Drupal.behaviors.nysenateHeader = {
    attach: function (context) {
      // Setup contextual variables.
      const isMicrositeLandingPage = document.querySelector('body.page-node-type-microsite-page');
      const isFrontpage = document.querySelector('body.path-frontpage');

      // Setup references to globally-used elements.
      const header = document.getElementById('js-sticky');
      const actionBar = document.querySelector('.c-actionbar');
      const headerBar = document.querySelector('section.c-header-bar');
      const senatorHero = document.querySelector('.hero--senator');
      const micrositeMenu = document.querySelector('.block-content--type-senator-microsite-menu');

      // Setup actionbar clone for homepage.
      let actionBarClone = null;
      if (isFrontpage && actionBar) {
        actionBarClone = actionBar.cloneNode(true);
        actionBarClone.classList.add('hidden');
        header.append(actionBarClone);
      }

      // Setup senator hero and menu clones for microsite landing pages.
      let senatorHeroClone = null;
      let micrositeMenuClone = null;
      if (isMicrositeLandingPage && senatorHero && micrositeMenu) {
        senatorHeroClone = senatorHero.cloneNode(true);
        micrositeMenuClone = micrositeMenu.cloneNode(true);
        senatorHeroClone.classList.add('l-header__collapsed');
        headerBar.append(senatorHeroClone, micrositeMenuClone);
      }

      // Call functions that implement header behaviors.
      this.stickyHeader(
        isMicrositeLandingPage,
        isFrontpage,
        actionBarClone,
        headerBar,
        senatorHero,
        senatorHeroClone,
        micrositeMenuClone,
      );
      this.jsSearchBox(isMicrositeLandingPage, micrositeMenuClone);
      this.mobileMenu();
    },

    /**
     * Implements dynamic "sticky" header scrolling behaviors.
     *
     * @returns void
     * @param isMicrositeLandingPage
     * @param isFrontpage
     * @param actionBarClone
     * @param headerBar
     * @param senatorHero
     * @param senatorHeroClone
     * @param micrositeMenuClone
     */
    stickyHeader: function (
      isMicrositeLandingPage,
      isFrontpage,
      actionBarClone,
      headerBar,
      senatorHero,
      senatorHeroClone,
      micrositeMenuClone,
    ) {
      const self = this;
      const inSession = document.querySelector('body.in-session');
      const navWrap = document.querySelector('.c-nav--wrap');
      const homepageHero = document.querySelector('.hero--homepage');

      // Implement dynamic sticky header behaviors for full site.
      let lastScrollTop = 0;
      window.addEventListener('scroll', function () {
        const currentScrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop;

        // Scrolling down.
        if (currentScrollTop > lastScrollTop) {
          // Shrink header.
          headerBar.classList.add('collapsed');
          document.body.classList.add('header-collapsed');

          if (!isMicrositeLandingPage) {
            // Hide menu.
            navWrap.classList.add('closed');
            document.body.classList.add('nav-collapsed');

            if (isFrontpage && !inSession && self.isScrolledBelowElement(homepageHero)) {
              // On frontpage, display actionbar in header when scrolled below
              // fixed actionbar.
              actionBarClone.classList.remove('hidden');
            }
          }
          else {
            if (self.isScrolledBelowElement(senatorHero)) {
              // On microsite landing page, display senator hero and menu in
              // header when scrolled below fixed hero and menu.
              senatorHeroClone.classList.add('expanded');
              micrositeMenuClone.classList.remove('expanded');
            }
          }
        }

        // Scrolling up.
        else {
          if (currentScrollTop < 50) {
            // Expand header when scrolled near the top.
            headerBar.classList.remove('collapsed');
            document.body.classList.remove('header-collapsed');
          }

          if (!isMicrositeLandingPage) {
            // Display nav menu in header when scrolling up.
            navWrap.classList.remove('closed');
            document.body.classList.remove('nav-collapsed');

            if (isFrontpage && !inSession && !self.isScrolledBelowElement(homepageHero)) {
              // On frontpage, hide actionbar from header when scrolled above
              // fixed actionbar.
              actionBarClone.classList.add('hidden');
            }
          }
          else {
            if (self.isScrolledBelowElement(senatorHero)) {
              // On microsite landing page, display menu when scrolling up.
              micrositeMenuClone.classList.add('expanded');
            }
            else {
              // On microsite landing page, hide menu and hero when scrolled
              // above fixed versions.
              senatorHeroClone.classList.remove('expanded');
              micrositeMenuClone.classList.remove('expanded');
            }
          }
        }

        lastScrollTop = currentScrollTop;
      });
    },

    /**
     * Implements header JS search box behaviors.
     *
     * @returns void
     * @param isMicrositeLandingPage
     * @param micrositeMenuClone
     */
    jsSearchBox: function (isMicrositeLandingPage, micrositeMenuClone) {
      const searchButtons = document.querySelectorAll('button.js-search--toggle');
      const searchForms = document.querySelectorAll('div.u-tablet-plus form.nys-searchglobal-form');
      const searchInputs = document.querySelectorAll('div.u-tablet-plus input.c-site-search--box');
      const navWraps = document.querySelectorAll('.c-nav--wrap');

      // Implement expandable search button in header for full site.
      searchButtons.forEach((searchButton, index) => {
        searchButton.addEventListener('click', (clickElem) => {
          let isHeaderSearchButton = clickElem.currentTarget.closest('.c-header-bar');
          navWraps.item(index).classList.toggle('search-open');
          searchForms.item(index).classList.toggle('open');
          if (!isMicrositeLandingPage) {
            document.body.classList.toggle('search-open');
          }
          if (micrositeMenuClone && isHeaderSearchButton) {
            micrositeMenuClone.classList.toggle('expanded-with-search');
          }
          if (navWraps.item(index).classList.contains('search-open')) {
            searchInputs.item(index).focus();
          }
        });
      });
    },

    /**
     * Implements mobile menu behaviors.
     *
     * @returns void
     */
    mobileMenu: function () {
      const mobileMenu = document.querySelector('button.js-mobile-nav--btn');
      mobileMenu.addEventListener('click', () => {
        document.body.classList.toggle('nav-open');
      });
    },

    /**
     * Check if window is scrolled below given element.
     *
     * @returns boolean
     * @param element
     */
    isScrolledBelowElement: function (element) {
      const elementRect = element.getBoundingClientRect();
      return elementRect.bottom < 0;
    },
  };
})(document, Drupal);
