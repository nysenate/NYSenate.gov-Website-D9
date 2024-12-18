(function (Drupal) {
  Drupal.behaviors.nysSchoolFormsConfirmationPopup = {
    attach: function (context) {
      const form = document.querySelector('form.node-student-submission-container-form');
      form.addEventListener('submit', (e) => {
        if (form.dataset.confirmationHandled === 'true') {
          return;
        }
        e.preventDefault();
        if (!document.getElementById('submission-confirmation-modal')) {
          const modal = document.createElement('div');
          modal.id = 'submission-confirmation-modal';
          modal.className = 'submission-modal-overlay';
          modal.innerHTML = `
            <div class="submission-modal">
              <h2>Wait!</h2>
              <p>Image orientations can change after upload.</p>
              <p><strong>Have you double-checked that all uploaded images have been rotated to their proper orientations?</strong></p>
              <p>(There is a rotate button next to each uploaded image.)</p>
              <div class="modal-buttons">
                <button id="confirm-no" class="modal-button">No, go back</button>
                <button id="confirm-yes" class="modal-button">Yes, I've confirmed that all image orientations are correct</button>
              </div>
            </div>
          `;
          document.body.appendChild(modal);
          document.getElementById('confirm-yes').addEventListener('click', () => {
            modal.remove();
            const submitButton = form.querySelector('div#edit-actions input.js-form-submit');
            if (submitButton) {
              form.dataset.confirmationHandled = 'true';
              submitButton.click();
            }
          });
          document.getElementById('confirm-no').addEventListener('click', () => {
            modal.remove();
          });
        }
      });
    },
  };
})(Drupal);
