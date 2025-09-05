/**
 * @file
 * Sets up the Aye/Nay voting widget seen on bill nodes.
 */
 'use strict';
(function ($, Drupal, drupalSettings) {

    Drupal.behaviors.nys_bill_voted = {
        attach: function (context, settings) {
            $('.c-bill--sentiment-update').hide();

            if ($('.alert-box-message').length != 0 && $('.c-bill--vote-widget').length == 0) {
                $('.c-bill--vote-attach').hide();
                $('.c-bill--vote-widget').addClass('c-bill--vote-attach');
                $('.c-bill--vote-widget').removeClass('c-bill--vote-widget');
            }

            if ($('.alert-box-message').length != 0 && $('.c-bill--vote-attach').length != 0) {
                $('.c-bill--vote-attach').show();
                // $('#edit-nys-bill-vote-button-wrapper').hide();
                var vote_value = $('[name="vote_value"]').val();
                var uid = $('[name="uid"]').val();
                if (vote_value == '0' && uid != "0") {
                    $('div.nys-bill-vote .c-bill-polling--cta').text("YOU ARE OPPOSED TO THIS BILL.");
                }
                else if (vote_value == '1' && uid != "0") {
                    $('div.nys-bill-vote .c-bill-polling--cta').text("YOU ARE IN FAVOR OF THIS BILL.");
                }
            }


            if ($('div.c-bill--sentiment-text').text().length) {
                $('html,body').animate({scrollTop: jQuery('div.nys-bill-vote').offset().top - 150}, 'slow');
            }

        }
    };
})(jQuery, Drupal, drupalSettings);
