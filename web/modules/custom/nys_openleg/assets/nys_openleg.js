(function ($, Drupal, once) {
  Drupal.behaviors.nys_openleg = {
    attach: function (context, settings) {
      // Add keyboard and click handling to the search toggle button.
      once('nys_openleg_keyboard', '#nys-openleg-search-form button.search-title').forEach(
        function (element) {
          $(element).on('click', function (e) {
            var form = $(this).closest('form');
            var expanded = $(this).attr('aria-expanded') === 'true';
            form.toggleClass('open');
            $(this).attr('aria-expanded', expanded ? 'false' : 'true');
          });
        }
      );

      // Add click handler to legacy .search-title elements (non-button).
      once('nys_openleg', '.search-title:not(button)', context).forEach(
        function (element) {
          $(element).on(
            'click', function (e) {
              $(e.target).closest('form').toggleClass('open');
            },
          );
        },
      );
    },
  };
})(jQuery, Drupal, once);
