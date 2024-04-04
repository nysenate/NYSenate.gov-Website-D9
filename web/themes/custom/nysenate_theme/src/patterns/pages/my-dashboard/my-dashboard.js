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
      let filterLabelOnce = once('nysDashboardFilterAccordion', '#block-exposed-form-my-dashboard-main > h2');
      filterLabelOnce.forEach(function (filterLabel) {
        filterLabel.addEventListener('click', function () {
          this.classList.toggle('active');
          let filterContainer = document.querySelector('#block-exposed-form-my-dashboard-main > .container');
          if (filterContainer.style.maxHeight) {
            filterContainer.style.maxHeight = null;
          } else {
            filterContainer.style.maxHeight = filterContainer.scrollHeight + 'px';
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
    let stickyFilterElem = document.getElementById('block-exposed-form-my-dashboard-main');
    let stickyFilterElemHeight = document
      .getElementById('block-exposed-form-my-dashboard-main')
      .getBoundingClientRect()
      .height;
    let sidebarWidth = document
      .querySelector('.region-sidebar-right')
      .getBoundingClientRect()
      .width
      - 40;
    let windowLocation = window.scrollY;
    let stickyLocation = document
      .querySelector('aside.layout-sidebar-right')
      .getBoundingClientRect()
      .top
      + windowLocation
      - 100;
    let bottomStickyLocation = document
      .querySelector('footer.l-footer')
      .getBoundingClientRect()
      .top
      + windowLocation
      - stickyFilterElemHeight
      - 120;

    if (windowLocation >= stickyLocation) {
      stickyFilterElem.classList.add('sticky-filter')
      stickyFilterElem.style.width = sidebarWidth + 'px';
    }
    else {
      stickyFilterElem.classList.remove('sticky-filter');
      stickyFilterElem.style.width = null;
    }
    if (windowLocation >= bottomStickyLocation) {
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
