(function (Drupal) {
  'use strict';

  Drupal.behaviors.nysOpenlegHistoryForm = {
    attach: function (context) {
      // Find all "View latest" links and attach click handlers.
      const links = context.querySelectorAll('.nys-openleg-history-view-latest');

      links.forEach(function (link) {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          // Forward to the same URL, minus any parameters.
          window.location.href = window.location.pathname;
        });
      });
    },
  };
})(Drupal);
