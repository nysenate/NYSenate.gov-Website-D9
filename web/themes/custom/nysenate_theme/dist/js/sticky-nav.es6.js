/**
 * @file
 * Behaviors for the Sticky Nav.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Sticky Nav behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.stickyNav = {
    attach: function attach() {
      var self = this;
      var nav = $('#js-page-nav');
      var navItems = $('#js-page-nav [class*=\'js-nav-item\']');
      var sections = $('[id^=\'section-\']');
      var pageNav = $('#js-sticky--clone .c-nav--wrap');
      var didScroll = false;
      var collapsedHeaderHeight = 140;
      var openHeaderHeight = 200;
      self.aboutPageNav(nav, navItems, pageNav, sections, didScroll, collapsedHeaderHeight, openHeaderHeight, self);
    },
    aboutPageNav: function aboutPageNav(nav, navItems, pageNav, sections, didScroll, collapsedHeaderHeight, openHeaderHeight, self) {
      // Find the offset for each section
      sections.each(function () {
        var thisSection = $('#' + this.id);
        var thisHeight = thisSection.outerHeight();
        this.topOffset = thisSection.offset().top;
        this.bottomOffset = this.topOffset + thisHeight;
      }); // bind .scrollTo to nav links

      navItems.on('click', function (e) {
        self.animateTo(e, collapsedHeaderHeight, openHeaderHeight);
      });
      $(window).scroll(function () {
        didScroll = true;
      });
      setInterval(function () {
        if (didScroll) {
          didScroll = false;
          self.checkScroll(collapsedHeaderHeight, openHeaderHeight, sections, nav, navItems, pageNav, self);
        }
      }, 250);
    },
    animateTo: function animateTo(e, collapsedHeaderHeight, openHeaderHeight) {
      e.preventDefault();
      var targetId = $(e.target).parent('li').attr('data-section');
      var offset;
      var currLoc = $(window).scrollTop();
      var targetLoc = $('#' + targetId).offset().top;
      /*
      if the current location is below the target, the nav will open so the offset should be based on that. otherwise the offset is the closed header height.
      */

      if (currLoc > targetLoc) {
        offset = openHeaderHeight - 2;
      } else {
        offset = collapsedHeaderHeight - 2;
      }

      $('html, body').animate({
        scrollTop: targetLoc - offset
      }, 750);
    },
    checkScroll: function checkScroll(collapsedHeaderHeight, openHeaderHeight, sections, nav, navItems, pageNav, self) {
      var winTop = $(window).scrollTop();
      var offset;

      if (!pageNav.hasClass('closed')) {
        offset = openHeaderHeight;
      } else {
        offset = collapsedHeaderHeight;
      }

      var boundaryTop = winTop + offset;
      var activeSection; // loop through sections. test to see if it's in the right place.

      sections.each(function () {
        if (this.topOffset <= boundaryTop && this.bottomOffset >= boundaryTop) {
          activeSection = this.id;
        }
      });
      self.setActiveNavItem(activeSection, navItems, nav); // collapse or expand menu

      if ($(window).scrollTop() > 100) {
        nav.addClass('collapsed');
      } else {
        nav.removeClass('collapsed');
      }
    },
    setActiveNavItem: function setActiveNavItem(active, navItems, nav) {
      // use the active section to set which link is active
      // and offset ul to show that item
      if (active !== undefined) {
        navItems.removeClass('active');
        nav.find('[data-section=\'' + active + '\']').addClass('active'); // move nav

        nav.children('ul').css('top', active.substr(active.length - 1) * -50 + 'px');
      }
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=sticky-nav.es6.js.map
