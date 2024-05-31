/**
 * @file
 * Behaviors for the Senator List.
 */

/* eslint-disable max-len */
!function (document, Drupal, $) {
  'use strict';
  /**
   * Setup and attach the Senator List behaviors.
   *
   * @type {Drupal~behavior}
   */

  Drupal.behaviors.senatorList = {
    attach: function attach() {
      var $committeeFilter = $('#edit-senator-committee-filter');
      var $partyFilter = $('#edit-field-party-value');
      $(document).ready(function () {
        $committeeFilter.add($partyFilter).on('change', function () {
          var $form = $(this).closest('form');
          $form.find('input[type=submit]').click();
        });
      });
    }
  };
}(document, Drupal, jQuery);
//# sourceMappingURL=nysenate-senator-list.js.map
