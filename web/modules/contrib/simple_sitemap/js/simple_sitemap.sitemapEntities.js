/**
 * @file
 * Attaches simple_sitemap behaviors to the sitemap entities form.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.behaviors.simpleSitemapEntities = {
    attach: function () {
      let $checkboxes = $('table tr input:checkbox:checked').once('simple-sitemap-entities');

      if ($checkboxes.length) {
        $checkboxes.on('change', function () {
          let $row = $(this).closest('tr');
          let $table = $row.closest('table');

          $row.toggleClass('color-success color-warning');

          let showWarning = $table.find('tr.color-warning').length > 0;
          let $warning = $('.simple-sitemap-entities-warning');

          if (showWarning && !$warning.length) {
            $(Drupal.theme('simpleSitemapEntitiesWarning')).insertBefore($table);
          }
          if (!showWarning && $warning.length) {
            $warning.remove();
          }
        });
      }
    }
  };

  $.extend(Drupal.theme, {
    simpleSitemapEntitiesWarning: function simpleSitemapEntitiesWarning() {
      return '<div class="simple-sitemap-entities-warning messages messages--warning" role="alert">'.concat(Drupal.t('The sitemap settings and any per-entity overrides will be deleted for the unchecked entity types.'), '</div>');
    }
  });

})(jQuery, Drupal);
