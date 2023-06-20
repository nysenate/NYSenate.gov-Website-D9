!function (document, Drupal, $) {
    'use strict';

    Drupal.behaviors.nysQuestionnairesManagement = {
        attach: function () {
            $('.nys-senators-management-issues-list')
            .on(
                'click', '.nys-senators-management-issue-follow-count', function (e) {
                    let $this = $(this),
                    $parent = $this.closest('.nys-senators-management-issues-list'),
                    $users_div = $parent.siblings('.issues-user-list').html('<h3>Loading . . .</h3>');
                    $users_div.load(window.location.href + '/' + $this.data('tid'));
                }
            );
        }
    }

}(document, Drupal, jQuery);
