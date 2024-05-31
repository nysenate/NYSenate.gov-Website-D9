/**
 * @file
 */

(function ($, Drupal) {

  Drupal.behaviors.rabbitHoleField = {
    attach: function (context, settings) {

      // Display the action in the vertical tab summary.
      $(context).find('.rabbit-hole-settings-form').drupalSetSummary(function (context) {
        var $action = $('[data-drupal-selector="edit-rabbit-hole-settings-0-action"] option:selected', context);
        return Drupal.checkPlain($action.text());
      });

    }
  }

})(jQuery, Drupal);
