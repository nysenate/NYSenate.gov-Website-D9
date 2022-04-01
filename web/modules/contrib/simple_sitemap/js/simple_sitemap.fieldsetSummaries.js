/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapFieldsetSummaries = {
    attach: function(context, settings) {
      $(context).find('#edit-simple-sitemap').drupalSetSummary(function(context) {
        var enabledVariants = [];
        $('input:radio.enabled-for-variant').each(function() {
          if ($(this).is(':checked') && $(this).val() == 1) {
            enabledVariants.push($(this).attr('class').split(' ')[1])
          }
        });

        if (enabledVariants.length > 0) {
          return Drupal.t('Included in sitemap variants: ') + enabledVariants.join(', ');
        }
        else {
          return Drupal.t('Excluded from all sitemap variants');
        }

      });
    }
  };
})(jQuery);
