/**
 * @file
 *
 * Behaviors for the Manage Dashboard form.
 */
((Drupal, once) => {
  Drupal.behaviors.nysManageDashboardUncheckAll = {
    attach: function () {
      let uncheckAllLinks = document.getElementsByClassName('uncheck-all-link');
      for (let uncheckAllLink of uncheckAllLinks) {
        uncheckAllLink.onclick = function () {
          let checkboxes = uncheckAllLink.closest('.description').nextElementSibling.getElementsByClassName('form-checkbox');
          for (let checkbox of checkboxes) {
            checkbox.checked = false;
          }
          return false;
        };
      }
    }
  };
})(Drupal, once);
