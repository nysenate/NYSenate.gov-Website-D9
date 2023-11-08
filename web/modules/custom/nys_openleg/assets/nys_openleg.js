(function ($, Drupal, once) {
  Drupal.behaviors.nys_openleg = {
    attach: function (context, settings) {
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
