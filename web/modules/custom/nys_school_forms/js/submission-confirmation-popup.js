(function (Drupal, $) {
  Drupal.behaviors.nysSchoolFormsConfirmationPopup = {
    attach: function (context) {
      const form = document.querySelector('form.node-student-submission-container-form');
      const form_submit = form.querySelector('div#edit-actions input.js-form-submit');
      const confirmation_modal = document.getElementById('submission-confirmation-modal');
      if (!form || !form_submit || !confirmation_modal) {
        return;
      }
      form.addEventListener('submit', (e) => {
        if (form.dataset.confirmationHandled === 'true') {
          return;
        }
        e.preventDefault();
        $('#submission-confirmation-modal').dialog({
          width: 640,
          modal: true,
          buttons: {
            "No, go back": function () {
              $(this).dialog("destroy");
              delete form.dataset.confirmationHandled;
            },
            "Yes, I've confirmed that all image orientations are correct": function () {
              $(this).dialog("destroy");
              form.dataset.confirmationHandled = 'true';
              form_submit.click();
            }
          },
          close: function () {
            $(this).dialog("destroy");
            delete form.dataset.confirmationHandled;
          }
        });
      });
    },
  };
})(Drupal, jQuery);
