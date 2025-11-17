(function(Drupal) {
  'use strict';

  Drupal.behaviors.nysOpenlegHistoryForm = {
    attach: function(context) {
      // Find all "View latest" links and attach click handlers.
      const links = context.querySelectorAll('.nys-openleg-history-view-latest');

      links.forEach(function(link) {
        link.addEventListener('click', function(e) {
          e.preventDefault();

          // Get the most recent date from the data attribute.
          const latestDate = this.getAttribute('data-latest');

          // Find the parent form and the history select element.
          const form = this.closest('form');
          if (!form) {
            console.warn('Could not find parent form for history link');
            return;
          }

          const selectField = form.querySelector('select[name="history"]');
          if (!selectField) {
            console.warn('Could not find history select field');
            return;
          }

          // Set the select to the most recent date and submit.
          selectField.value = latestDate;
          form.submit();
        });
      });
    }
  };
})(Drupal);
