(function ($, Drupal, once) {
  Drupal.behaviors.NYS_Openleg = {
    attach: function (context, settings) {
      once('NYS_Openleg', '.search-title', context).forEach(
          function (element) {
            $(element).on(
                'click', function (e) {
                  $(e.target).closest('form').toggleClass('open');
                }
            );
          }
      );
    }
  };
})(jQuery, Drupal, once);
