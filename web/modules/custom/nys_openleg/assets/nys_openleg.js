(function ($, Drupal, once) {
  Drupal.behaviors.nys_openleg = {
    attach: function (context, settings) {
      // Add keyboard awareness to the search drop-down.
      once('nys_openleg_keyboard', '#nys-openleg-search-form h3.search-title').forEach(
        function (element) {
          $(element).on('keydown', function (e) {
            if (e.which == 13 || e.which == 32) {
              $(this).click();
            }
          });
        }
      );

      // Add click handler to search title.
      once('nys_openleg', '.search-title', context).forEach(
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
