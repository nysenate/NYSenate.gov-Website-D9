!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysQuestionnairesManagement = {
    attach: function () {
      $('.nys-senators-management-dashboard-questionnaires')
          .on('click', '.sponsored-questionnaire, .other-questionnaire', function (e) {
            let $this = $(this),
                $parent = $this.closest('.tab-content'),
                $users_div = $parent.children('.questionnaire-user-list').html('<h3>Loading . . .</h3>')
            ;
            $users_div.load(window.location.href + '/' + $this.data('qid'));
          })
          .on('click', '.tab', function (e) {
            let $this = $(this),
                $parent = $this.closest('.nys-senators-management-dashboard-questionnaires'),
                $target = $parent.children('#' + $this.data('target'))
            ;
            $parent.find('.tab.active,.tab-content.active').removeClass('active');
            $this.addClass('active');
            $target.addClass('active');
          });
    }
  }

}(document, Drupal, jQuery);
