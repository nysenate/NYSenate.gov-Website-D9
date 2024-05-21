/**
 * @file
 *
 * Behaviors for the My Dashboard page.
 */
((Drupal, once) => {
  Drupal.behaviors.nysDashboardFilterAccordion = {
    attach: function () {
      if (!inMobileMode()) {
        return;
      }
      let accordionButtonOnce = once('nysDashboardFilterAccordion', '#dashboard-filters-accordion-button');
      accordionButtonOnce.forEach(function (accordionButton) {
        accordionButton.setAttribute('aria-expanded', false);
        accordionButton.addEventListener('click', function () {
          this.classList.toggle('active');
          let accordionContainer = document.getElementById('dashboard-filters-accordion');
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
    attach: function () {
      if (inMobileMode()) {
        return;
      }
      setStickyFilterClasses();
      window.onscroll = function () {setStickyFilterClasses()};
    }
  };

  function setStickyFilterClasses() {
    let stickyTopLocation = 120;
    let windowLocation = window.scrollY;
    let contentHeight = document
      .querySelector('.view-my-dashboard')
      .getBoundingClientRect()
      .height;
    let stickyFilterElem = document.getElementById('block-exposed-form-my-dashboard-main');
    let stickyFilterElemHeight = stickyFilterElem
      .getBoundingClientRect()
      .height;
    let sidebarWidth = document
      .querySelector('.region-sidebar-right')
      .getBoundingClientRect()
      .width;
    let topStickyTrigger = document
      .querySelector('.region-sidebar-right')
      .getBoundingClientRect()
      .top
      + windowLocation
      - stickyTopLocation;
    let bottomStickyTrigger = document
      .querySelector('footer.l-footer')
      .getBoundingClientRect()
      .top
      + windowLocation
      - stickyFilterElemHeight
      - stickyTopLocation;
    if (stickyFilterElemHeight >= contentHeight) {
      return;
    }
    if (windowLocation > topStickyTrigger) {
      stickyFilterElem.classList.add('sticky-filter')
      stickyFilterElem.style.width = sidebarWidth + 'px';
      stickyFilterElem.style.top = stickyTopLocation + 'px';
    }
    else {
      stickyFilterElem.classList.remove('sticky-filter');
      stickyFilterElem.style.width = null;
      stickyFilterElem.style.top = null;
    }
    if (windowLocation >= bottomStickyTrigger) {
      stickyFilterElem.classList.add('sticky-filter-bottom')
    }
    else {
      stickyFilterElem.classList.remove('sticky-filter-bottom');
    }
  }

  function inMobileMode() {
    let rightSidebarElem = document.querySelector('.layout-sidebar-right');
    let rightSidebarInMobileMode = window.getComputedStyle(rightSidebarElem).getPropertyValue('order');
    return rightSidebarInMobileMode === '1';
  }
})(Drupal, once);
