((Drupal) => {
  Drupal.behaviors.pagerRemoveRole = {
    attach: function (context, settings) {
      document.querySelectorAll('.managed-csv-datatable-container').forEach(function (container) {
        // Find the table within this container
        const pager = container.querySelector('.dt-paging nav');
        if (pager) {
          // Attach a listener for the 'draw.dt' event using vanilla JavaScript
          pager.addEventListener('draw.dt', function() {
            // Select all 'a' tags within the table and remove the 'role' attribute
            this.querySelectorAll('a[role="link"]').forEach(function(link) {
              link.removeAttribute('role');
            });
          });
        }
      });
    }
  };
})(Drupal);
