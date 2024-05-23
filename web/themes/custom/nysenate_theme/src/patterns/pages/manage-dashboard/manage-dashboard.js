/**
 * @file
 *
 * Behaviors for the Manage Dashboard form.
 */
((Drupal, once) => {
  Drupal.behaviors.nysManageDashboard = {
    attach: function () {
      // Disable submit button by default
      let submitButton = document.querySelector('#nys-dashboard-manage-dashboard #edit-submit');
      submitButton.disabled = true;

      // Enable submit button if any input unchecked.
      let fields = document.querySelectorAll('#nys-dashboard-manage-dashboard input.form-checkbox');
      fields = Array.from(fields);
      fields.forEach(field => {
        field.addEventListener('change', () => {
          submitButton.disabled = fields.every(field => field.checked); //field.checked;
        })
      })

      // Provides "uncheck all" functionality.
      let uncheckAllButtons = document.getElementsByClassName('uncheck-all-button');
      for (let uncheckAllButton of uncheckAllButtons) {
        uncheckAllButton.onclick = function () {
          let checkboxes = uncheckAllButton.closest('.form-checkboxes').getElementsByClassName('form-checkbox');
          for (let checkbox of checkboxes) {
            checkbox.checked = false;
            submitButton.disabled = false;
          }
          return false;
        };
      }
    }
  };
})(Drupal, once);
