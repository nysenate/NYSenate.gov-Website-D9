/**
 * @file
 * Attaches simple_sitemap behaviors to the sitemap entities form.
 */
(function($) {

  "use strict";

  Drupal.behaviors.simple_sitemapSitemapEntities = {
    attach: function(context, settings) {
      $.each(settings.simple_sitemap.all_entities, function(index, entityId) {
        var target = '#edit-' + entityId + '-enabled';
        triggerVisibility(target, entityId);

        $(target).change(function() {
          triggerVisibility(target, entityId);
        });
      });

      function triggerVisibility(target, entityId) {
        if ($(target).is(':checked')) {
          $('#warning-' + entityId).hide();
          $('#indexed-bundles-' + entityId).show();
        }
        else {
          $('#warning-' + entityId).show();
          $('#indexed-bundles-' + entityId).hide();
        }
      }
    }
  };
})(jQuery);
