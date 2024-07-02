/**
 * @file
 * Attaches simple_sitemap behaviors to the sitemap entities form.
 */
(($, Drupal, once) => {
  Drupal.behaviors.simpleSitemapEntities = {
    attach() {
      const $checkboxes = $(
        once(
          'simple-sitemap-entities',
          'table tr input[type=checkbox][checked]',
        ),
      );

      if ($checkboxes.length) {
        $checkboxes.on('change', function change() {
          const $row = $(this).closest('tr');
          const $table = $row.closest('table');

          $row.toggleClass('color-success color-warning');

          const showWarning = $table.find('tr.color-warning').length > 0;
          const $warning = $('.simple-sitemap-entities-warning');

          if (showWarning && !$warning.length) {
            $(Drupal.theme('simpleSitemapEntitiesWarning')).insertBefore(
              $table,
            );
          }
          if (!showWarning && $warning.length) {
            $warning.remove();
          }
        });
      }
    },
  };

  $.extend(Drupal.theme, {
    simpleSitemapEntitiesWarning() {
      return `<div class="simple-sitemap-entities-warning messages messages--warning" role="alert">${Drupal.t(
        'The sitemap settings and any per-entity overrides will be deleted for the unchecked entity types.',
      )}</div>`;
    },
  });
})(jQuery, Drupal, once);
