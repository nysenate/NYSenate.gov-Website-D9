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

  Drupal.behaviors.nysDashboardUncheckAll = {
    attach: function () {
      let uncheckAllLinks = document.getElementsByClassName('uncheck-all-link');
      for (let uncheckAllLink of uncheckAllLinks) {
        uncheckAllLink.onclick = function () {
          let checkboxes = uncheckAllLink.closest('.description').nextElementSibling.getElementsByClassName('form-checkbox');
          for (let checkbox of checkboxes) {
            checkbox.checked = false;
          }
          return false;
        };
      }
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
