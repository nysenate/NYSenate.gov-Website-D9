/**
 * @file
 * Implementation of check toggling using AJAX.
 */

(function ($) {
  Drupal.behaviors.securityReview = {
    attach: function (context) {
      $(context).find('.security-review-toggle-link a').click(function () {
        var link = $(this);
        var url = link.attr('href');
        var td = link.parent();
        var tr = td.parent();
        $.getJSON(url + '&js=1', function (data) {
          if (data.skipped) {
            tr.addClass('skipped');
          }
          else {
            tr.removeClass('skipped');
          }
          link.text(data.toggle_text);
          link.attr(data.toggle_href);
        });
        return false;
      });
    }
  };
})(jQuery);
