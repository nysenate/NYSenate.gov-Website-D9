/**
 * @file
 * Confirms bill positions before submitting the Drupal voting form.
 */
(function ($, Drupal, drupalSettings) {
    'use strict';

    $.fn.nysBillVoteConfirm = function (voteValue, billName) {
        var $form = this.closest('form'),
            modalId = $form.attr('id') + '-confirmation',
            $existing = $('#' + modalId);

        if ($existing.length) {
            $existing.remove();
        }

        var $modal = $('<dialog/>', {
                id: modalId,
                class: 'reveal-modal small nys-bill-vote-confirmation',
                'aria-label': Drupal.t('Confirm your position'),
                role: 'dialog'
            }),
            $confirm = $('<button/>', {
                type: 'button',
                class: 'default button',
                text: Drupal.t('Confirm')
            }),
            $cancel = $('<button/>', {
                type: 'button',
                class: 'secondary button',
                text: Drupal.t('Cancel')
            }),
            text = voteValue === 'yes'
                ? Drupal.t('Confirm your support for @bill', {'@bill': billName})
                : Drupal.t('Confirm your opposition to @bill', {'@bill': billName});

        $modal.append($('<p/>').text(text), $('<p/>').append($confirm, $cancel));
        $('body').append($modal);
        $confirm.one('click', function () {
            $confirm.prop('disabled', true);
            $modal[0].close();
            $form.find('[data-confirm-vote="' + voteValue + '"]').trigger('click');
        });
        $cancel.on('click', function () {
            $modal[0].close();
        });
        $modal[0].showModal();
    };

    $.fn.nysBillVoteIntent = function (voteValue) {
        var $form = this,
            intentText = voteValue === 'yes' ? Drupal.t('support') : Drupal.t('oppose'),
            $target = $form.find('.nys-bill-vote-' + voteValue);

        $target.addClass('selected accent-bg').siblings('.nys-bill-vote-yes, .nys-bill-vote-no')
            .removeClass('selected accent-bg');
        $('.c-bill--vote-widget').addClass('c-bill--vote-attach').removeClass('c-bill--vote-widget');
        $('.c-bill--message-form, .c-bill--sentiment-update').show();
        $('#nys-bills-bill-form input[name=vote_value]').val(voteValue);
        $('#nys-bills-bill-form input[type="submit"]').val(voteValue === 'yes'
            ? Drupal.t('Support this bill')
            : Drupal.t('Oppose this bill'));
        $form.find('.c-bill-polling--cta').text(
            Drupal.t('Complete this form to @intent this bill:', {'@intent': intentText})
        );
        if ($form.hasClass('nys-bill-vote-form-embedded')) {
            $('.nys-bill-subscribe').addClass('js-hide');
        }
    };

    $.fn.nysBillVoteRecorded = function (voteValue, isSubscribed) {
        var $form = this,
            $target = $form.find('.nys-bill-vote-' + voteValue),
            buttonText = voteValue === 'yes'
                ? Drupal.t('Support this bill')
                : Drupal.t('Oppose this bill');

        $target.addClass('selected accent-bg current-vote').removeClass('change-vote');
        $target.siblings('.nys-bill-vote-yes, .nys-bill-vote-no')
            .removeClass('selected accent-bg current-vote').addClass('change-vote');

        if (isSubscribed) {
            $form.find('.nys-subscribe-button').replaceWith(
                $('<div/>', {class: 'subscribe_result', text: Drupal.t('You Are Subscribed.')})
            );
        }
        $form.find('.c-bill-polling--cta').text(voteValue === 'yes'
            ? Drupal.t('You are in favor of this bill')
            : Drupal.t('You are opposed to this bill'));

        if ($form.hasClass('nys-bill-vote-form-embedded')) {
            return;
        }

        $form.find('.subscribe_result').prev('hr').remove();
        $form.find('.subscribe_result').before('<hr />');
        $('.c-bill--vote-widget').addClass('c-bill--vote-attach').removeClass('c-bill--vote-widget');
        $('.c-bill--message-form, .c-bill--sentiment-update').show();
        $('#nys-bills-bill-form input[name=vote_value]').val(voteValue);
        $('#nys-bills-bill-form input[type="submit"]').val(buttonText);
        if ($form.find('.nys-bill-vote').length) {
            $('html, body').animate({scrollTop: $form.offset().top - 250}, 'slow');
        }
    };

    Drupal.behaviors.nysBillVote = {
        attach: function (context) {
            if ($('#nys-bills-bill-form input[name=register]', context).prop('checked') === false
                && !drupalSettings.settings.is_logged_in) {
                $('.c-bill--message-form .form-item-message', context).hide();
            }

            var intent = new URLSearchParams(window.location.search).get('intent'),
                $form = $('.nys-bill-vote-form:not(.nys-bill-vote-form-embedded)', context).first();

            if (!['support', 'oppose'].includes(intent) || !$form.length) {
                return;
            }
            if ($form.attr('data-intent-processed')) {
                return;
            }
            $form.attr('data-intent-processed', 'true');

            var voteValue = intent === 'support' ? 'yes' : 'no';
            if (drupalSettings.settings.is_logged_in) {
                var billName = (drupalSettings.bill_vote && drupalSettings.bill_vote.bill_name) || document.title;
                $form.nysBillVoteConfirm(voteValue, billName);
            }
            else {
                $form.nysBillVoteIntent(voteValue);
            }
        }
    };
})(jQuery, Drupal, drupalSettings);
