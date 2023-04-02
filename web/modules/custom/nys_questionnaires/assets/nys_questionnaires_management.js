!function (document, Drupal, $) {
  'use strict';

  Drupal.behaviors.nysQuestionnairesManagement = {
    attach: function() {
      $('.nys-senators-management-dashboard-questionnaires').on('click', '.sponsored-questionnaire, .other-questionnaire', function(e) {
        let $this = $(this),
            $parent = $this.closest('.tab-content'),
            $users_div = $parent.children('.questionnaire-user-list').html('<h3>Loading . . .</h3>')
        ;
        $users_div.load(window.location.href + '/' + $this.data('qid'));
      });
    }
  };
}(document, Drupal, jQuery);
