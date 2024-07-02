/**
 * @file
 *
 * Behaviors for the My Dashboard page.
 */
(function (Drupal, once) {
  Drupal.behaviors.nysDashboardFilterAccordion = {
    attach: function attach() {
      if (!inMobileMode()) {
        return;
      }

      var accordionButtonOnce = once('nysDashboardFilterAccordion', '#dashboard-filters-accordion-button');
      accordionButtonOnce.forEach(function (accordionButton) {
        accordionButton.setAttribute('aria-expanded', false);
        accordionButton.addEventListener('click', function () {
          this.classList.toggle('active');
          var accordionContainer = document.getElementById('dashboard-filters-accordion');

          if (accordionContainer.style.maxHeight) {
            accordionButton.setAttribute('aria-expanded', false);
            accordionContainer.style.maxHeight = null;
          } else {
            accordionButton.setAttribute('aria-expanded', true);
            accordionContainer.style.maxHeight = accordionContainer.scrollHeight + 'px';
          }
        });
      });
    }
  };
  Drupal.behaviors.nysDashboardStickyFilters = {
    attach: function attach() {
      if (inMobileMode()) {
        return;
      }

      setStickyFilterClasses();

      window.onscroll = function () {
        setStickyFilterClasses();
      };
    }
  };

  function setStickyFilterClasses() {
    var stickyTopLocation = 120;
    var windowLocation = window.scrollY;
    var contentHeight = document.querySelector('.view-my-dashboard').getBoundingClientRect().height;
    var stickyFilterElem = document.getElementById('block-exposed-form-my-dashboard-main');
    var stickyFilterHeight = stickyFilterElem.getBoundingClientRect().height;
    var stickyFilterWidth = stickyFilterElem.getBoundingClientRect().width;
    var topStickyTrigger = document.querySelector('.region-sidebar-right').getBoundingClientRect().top + windowLocation - stickyTopLocation;
    var bottomStickyTrigger = document.querySelector('footer.l-footer').getBoundingClientRect().top + windowLocation - stickyFilterHeight - stickyTopLocation;

    if (stickyFilterHeight >= contentHeight) {
      return;
    }

    if (windowLocation > topStickyTrigger) {
      stickyFilterElem.classList.add('sticky-filter');
      stickyFilterElem.style.width = stickyFilterWidth + 'px';
      stickyFilterElem.style.top = stickyTopLocation + 'px';
    } else {
      stickyFilterElem.classList.remove('sticky-filter');
      stickyFilterElem.style.width = null;
      stickyFilterElem.style.top = null;
    }

    if (windowLocation >= bottomStickyTrigger) {
      stickyFilterElem.classList.add('sticky-filter-bottom');
    } else {
      stickyFilterElem.classList.remove('sticky-filter-bottom');
    }
  }

  function inMobileMode() {
    var rightSidebarElem = document.querySelector('.layout-sidebar-right');
    var rightSidebarInMobileMode = window.getComputedStyle(rightSidebarElem).getPropertyValue('order');
    return rightSidebarInMobileMode === '1';
  }
})(Drupal, once);
//# sourceMappingURL=my-dashboard.js.map
