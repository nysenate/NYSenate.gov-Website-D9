!((document, Drupal, once) => {
  'use strict';
  Drupal.behaviors.nysenateHeader = {
    attach: function (context) {
      // Setup contextual variables.
      const isMicrositeLandingPage = document.querySelector('body.page-node-type-microsite-page');
      const isFrontpage = document.querySelector('body.path-frontpage');

      // Setup references to globally-used elements.
      // once() ensures this behavior is only initialized once per page load.
      // All sub-functions attach document-level listeners; without this guard
      // they would accumulate on every attach() call (e.g. after AJAX).
      const header = once('nys-header-once', '#js-sticky').shift();
      if (!header) return;
      const actionBar = document.querySelector('.c-actionbar');
      const headerBar = once('nys-header-bar-once', 'section.c-header-bar').shift();
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
      this.handleResponsiveMenu();
      this.userMenu();
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
      const desktopMediaQuery = window.matchMedia('(min-width: 768px)');

      // Implement dynamic sticky header behaviors for full site.
      let lastScrollTop = 0;
      window.addEventListener('scroll', function () {
        const currentScrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop;

        // Scrolling down.
        if (currentScrollTop > lastScrollTop) {
          // Shrink header.
          if (currentScrollTop > 100) {
            headerBar.classList.add('collapsed');
          }

          if (!isMicrositeLandingPage) {
            // Hide menu.
            navWrap.classList.add('closed');

            // On desktop: prevent focus to hidden (scrolled-away) menu items.
            if (desktopMediaQuery.matches) {
              navWrap.setAttribute('inert', '');
            }

            // On frontpage, display actionbar in header when scrolled below
            // fixed actionbar.
            if (isFrontpage && !inSession && self.isScrolledBelowElement(homepageHero)) {
              actionBarClone.classList.remove('hidden');
            }
          }

          // On microsite landing page, display senator hero and menu in
          // header when scrolled below fixed hero and menu.
          else {
            if (self.isScrolledBelowElement(senatorHero)) {
              senatorHeroClone.classList.add('expanded');
              micrositeMenuClone.classList.remove('expanded');
            }
          }
        }

        // Scrolling up.
        else {
          // Expand header when scrolled near the top.
          if (currentScrollTop < 100) {
            headerBar.classList.remove('collapsed');
          }

          // Display nav menu in header when scrolling up.
          if (!isMicrositeLandingPage) {
            navWrap.classList.remove('closed');

            // On desktop: restore focusability when menu is visible again.
            if (desktopMediaQuery.matches) {
              navWrap.removeAttribute('inert');
            }

            // On frontpage, hide actionbar from header when scrolled above
            // fixed actionbar.
            if (isFrontpage && !inSession && !self.isScrolledBelowElement(homepageHero)) {
              actionBarClone.classList.add('hidden');
            }
          }

          // On microsite landing page.
          else {
            // Display menu when scrolling up.
            if (self.isScrolledBelowElement(senatorHero)) {
              micrositeMenuClone.classList.add('expanded');
            }
            // Hide menu and hero when scrolled above fixed versions.
            else {
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
      const searchForms = document.querySelectorAll('div.u-tablet-plus form.c-site-search, div.u-tablet-plus form.nys-searchglobal-form, div.u-tablet-plus form.nys-global-search-form');
      // Use the container so inert covers siblings like .c-site-search--link outside the form.
      const searchContainers = Array.from(searchForms).map(f => f.closest('.c-site-search--container') || f);
      const searchInputs = document.querySelectorAll('div.u-tablet-plus input.c-site-search--box');
      const navWraps = document.querySelectorAll('.c-nav--wrap');

      // Implement expandable search button in header for full site.
      searchButtons.forEach((searchButton, index) => {
        // Set initial inert state on closed search containers
        if (!searchForms.item(index).classList.contains('open')) {
          searchContainers[index].setAttribute('inert', '');
        }

        // Extracted close function for reuse (click and ESC).
        const closeSearch = () => {
          navWraps.item(index).classList.remove('search-open');
          searchForms.item(index).classList.remove('open');
          searchButton.setAttribute('aria-expanded', 'false');
          searchButton.innerHTML = 'open search';
          searchContainers[index].setAttribute('inert', '');
          if (!isMicrositeLandingPage) {
            document.body.classList.remove('search-open');
          }
          if (micrositeMenuClone) {
            micrositeMenuClone.classList.remove('expanded-with-search');
          }
          searchButton.focus();
        };

        searchButton.addEventListener('click', (clickElem) => {
          let isHeaderSearchButton = clickElem.currentTarget.closest('.c-header-bar');
          navWraps.item(index).classList.toggle('search-open');
          searchForms.item(index).classList.toggle('open');
          clickElem.currentTarget.setAttribute('aria-expanded', searchForms.item(index).classList.contains('open') ? 'true' : 'false');
          clickElem.currentTarget.innerHTML = (searchForms.item(index).classList.contains('open') ? 'close' : 'open') + ' search';
          
          // Toggle inert on the container to cover form and sibling .c-site-search--link.
          if (searchForms.item(index).classList.contains('open')) {
            searchContainers[index].removeAttribute('inert');
          } else {
            searchContainers[index].setAttribute('inert', '');
          }
          
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

        // Close search overlay when ESC is pressed while focus is within search.
        searchForms.item(index).addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && searchForms.item(index).classList.contains('open')) {
            e.preventDefault();
            closeSearch();
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
      const hamburgerButton = document.querySelector('button.c-nav--toggle');
      const closeMenuButton = document.querySelector('button.c-nav--toggle--close');

      // On regular pages the nav is a standalone <div id="main-site-menu">.
      // On senator/microsite pages, the same role is filled by .c-nav--wrap
      // inside the microsite menu block — it has no id by default.
      // Assign the expected id so aria-controls points to the right element.
      let navMenu = document.getElementById('main-site-menu');
      if (!navMenu) {
        navMenu = document.querySelector('.c-nav--wrap');
        if (navMenu) navMenu.id = 'main-site-menu';
      }
      const mobileMediaQuery = window.matchMedia('(max-width: 767px)');

      if (!hamburgerButton || !navMenu) return;

      // Returns all currently focusable elements within the nav overlay.
      const getFocusableElements = () => {
        return [...navMenu.querySelectorAll(
          'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )];
      };

      const openMenu = () => {
        document.body.classList.add('nav-open');
        hamburgerButton.setAttribute('aria-expanded', 'true');
        navMenu.removeAttribute('inert');
        const focusable = getFocusableElements();
        if (focusable.length) focusable[0].focus();
      };

      const closeMenu = () => {
        document.body.classList.remove('nav-open');
        hamburgerButton.setAttribute('aria-expanded', 'false');
        navMenu.setAttribute('inert', '');
        hamburgerButton.focus();
      };

      // Set initial inert state: nav is off-screen on mobile until opened.
      if (mobileMediaQuery.matches) {
        navMenu.setAttribute('inert', '');
      }

      // Update inert state on viewport breakpoint change.
      mobileMediaQuery.addEventListener('change', (e) => {
        if (e.matches) {
          // Switched to mobile: mark nav inert if not open.
          if (!document.body.classList.contains('nav-open')) {
            navMenu.setAttribute('inert', '');
          }
        } else {
          // Switched to desktop: nav is always visible, remove mobile inert.
          navMenu.removeAttribute('inert');
        }
      });

      // Focus trap: keep Tab/Shift+Tab within the open mobile nav overlay.
      navMenu.addEventListener('keydown', (e) => {
        if (!document.body.classList.contains('nav-open') || e.key !== 'Tab') return;

        const focusable = getFocusableElements();
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      });

      hamburgerButton.addEventListener('click', () => {
        if (document.body.classList.contains('nav-open')) {
          closeMenu();
        } else {
          openMenu();
        }
      });

      if (closeMenuButton) {
        closeMenuButton.addEventListener('click', () => closeMenu());
      }

      // ESC closes the mobile menu and returns focus to the toggle button.
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('nav-open')) {
          closeMenu();
        }
      });
    },

    /**
     * Implements responsive menu behavior for tablet and desktop.
     *
     * @returns void
     */
    handleResponsiveMenu: function () {
      const mediaQuery = window.matchMedia('(min-width: 768px)');
      const mobileMenuButton = document.querySelector('button.js-mobile-nav--btn.button--menu');

      const handleBreakpointChange = (e) => {
        if (e.matches) {
          document.body.classList.remove('nav-open');
          if (mobileMenuButton) {
            mobileMenuButton.setAttribute('aria-expanded', 'false');
          }
        }
      };

      // Check initial state.
      handleBreakpointChange(mediaQuery);

      // Listen for breakpoint changes.
      mediaQuery.addEventListener('change', handleBreakpointChange);
    },

    /**
     * Implements accessible flyout behavior for the logged-in user menu.
     *
     * @returns void
     */
    userMenu: function () {
      const toggleButton = document.querySelector('.c-user-menu__toggle');
      const panel = document.getElementById('user-menu-dropdown');

      if (!toggleButton || !panel) return;

      const openPanel = () => {
        panel.hidden = false;
        toggleButton.setAttribute('aria-expanded', 'true');
        const first = panel.querySelector('a, button');
        if (first) first.focus();
      };

      const closePanel = (returnFocus = true) => {
        panel.hidden = true;
        toggleButton.setAttribute('aria-expanded', 'false');
        if (returnFocus) toggleButton.focus();
      };

      toggleButton.addEventListener('click', () => {
        if (toggleButton.getAttribute('aria-expanded') === 'true') {
          closePanel();
        } else {
          openPanel();
        }
      });

      // ESC closes the panel and returns focus to the toggle button.
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && toggleButton.getAttribute('aria-expanded') === 'true') {
          closePanel();
        }
      });

      // Close the panel when focus moves outside it.
      document.addEventListener('focusin', (e) => {
        if (
          toggleButton.getAttribute('aria-expanded') === 'true' &&
          !panel.contains(e.target) &&
          !toggleButton.contains(e.target)
        ) {
          closePanel(false);
        }
      });

      // Close the panel when clicking outside it.
      // Use contains() so clicks on child elements of the toggle button
      // (text spans, SVG path) don't trigger an immediate close.
      document.addEventListener('click', (e) => {
        if (
          toggleButton.getAttribute('aria-expanded') === 'true' &&
          !panel.contains(e.target) &&
          !toggleButton.contains(e.target)
        ) {
          closePanel(false);
        }
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
})(document, Drupal, once);
