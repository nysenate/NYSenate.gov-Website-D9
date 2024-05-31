(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.quote = {
    attach: function (context, settings) {

      $('#privatemsg-messages table tbody tr').once().each(function() {
        if ($(this).find('span.marker').length) {
          $(this).addClass('privatemsg-unread');
        }
      });

    }
  };

})(jQuery, Drupal);
