/**
 * @file
 */

(function ($, Drupal) {

  Drupal.behaviors.nodeRevisionDeleteSummaies = {
    attach: function (context, settings) {

      // Display the action in the vertical tab summary.
      $(context).find('.node-revision-delete-settings-form').drupalSetSummary(function (context) {
        let summary = '', enabledPlugins = [];
        $('.node-revision-delete-plugin-settings').each(function () {
          if ($(this).find('input:checked').length) {
            enabledPlugins.push($(this).find('summary').text());
          }
        });

        if (enabledPlugins.length > 0) {
          summary = Drupal.t("Enabled Plugins: ") + enabledPlugins.join(" ");
        }
        else {
          summary = Drupal.t("Node Revision Delete is disabled.");
        }

        return summary;
      });
    }
  }

})(jQuery, Drupal);
