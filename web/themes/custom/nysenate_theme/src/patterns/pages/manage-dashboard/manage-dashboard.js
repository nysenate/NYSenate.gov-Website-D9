/**
 * @file
 *
 * Behaviors for the Manage Dashboard form.
 */
((Drupal, once) => {
  Drupal.behaviors.nysManageDashboardUncheckAll = {
    attach: function () {
      let uncheckAllButtons = document.getElementsByClassName('uncheck-all-button');
      for (let uncheckAllButton of uncheckAllButtons) {
        uncheckAllButton.onclick = function () {
          let checkboxes = uncheckAllButton.closest('.form-checkboxes').getElementsByClassName('form-checkbox');
          for (let checkbox of checkboxes) {
            checkbox.checked = false;
          }
          return false;
        };
      }
    }
  };
})(Drupal, once);
