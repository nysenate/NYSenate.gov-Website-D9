/**
 * @file
 *
 * Behaviors for the My Dashboard page.
 */
((Drupal, once) => {
  Drupal.behaviors.nysDashboardFilterAccordion = {
    attach: function () {
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
})(Drupal, once);
