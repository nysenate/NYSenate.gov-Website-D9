/**
 * @file
 * Attaches simple_sitemap behaviors to the entity form.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.simpleSitemapFieldsetSummaries = {
    attach: function (context) {
      $(context).find('.simple-sitemap-fieldset').drupalSetSummary(function (context) {
        let summary = '', enabledVariants = [];

        $(context).find('input:checkbox[name*="simple_sitemap_index_now"]').each(function () {
          summary = (this.checked ? Drupal.t('IndexNow notification enabled') : Drupal.t('IndexNow notification disabled')) + ', ';
        });

        $(context).find('input:radio:checked[data-simple-sitemap-label][value="1"]').each(function () {
          enabledVariants.push(this.dataset.simpleSitemapLabel);
        });

        if (enabledVariants.length > 0) {
          summary += Drupal.t('Included in sitemaps: ') + enabledVariants.join(', ');
        }
        else {
          summary += Drupal.t('Excluded from all sitemaps');
        }

        return summary;
      });
    }
  };

})(jQuery, Drupal);
