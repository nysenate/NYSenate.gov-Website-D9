/**
 * @file
 * Views UI helpers for Simple XML Sitemap display extender.
 */

(($, Drupal, once) => {
  Drupal.simpleSitemapViewsUi = {};

  Drupal.behaviors.simpleSitemapViewsUiArguments = {
    attach() {
      const $arguments = $(
        once('simple-sitemap-views-ui-arguments', '.indexed-arguments'),
      );

      if ($arguments.length) {
        $arguments.each(function each() {
          const $checkboxes = $(this).find('input[type="checkbox"]');

          if ($checkboxes.length) {
            // eslint-disable-next-line no-new
            new Drupal.simpleSitemapViewsUi.Arguments($checkboxes);
          }
        });
      }
    },
  };

  // eslint-disable-next-line func-names
  Drupal.simpleSitemapViewsUi.Arguments = function ($checkboxes) {
    this.$checkboxes = $checkboxes;
    this.$checkboxes.on('change', $.proxy(this, 'changeHandler'));
  };

  // eslint-disable-next-line func-names
  Drupal.simpleSitemapViewsUi.Arguments.prototype.changeHandler = function (e) {
    const $checkbox = $(e.target);
    const index = this.$checkboxes.index($checkbox);

    if ($checkbox.prop('checked')) {
      this.check(index);
    } else {
      this.uncheck(index);
    }
  };

  // eslint-disable-next-line func-names
  Drupal.simpleSitemapViewsUi.Arguments.prototype.check = function (index) {
    this.$checkboxes.slice(0, index).prop('checked', true);
  };

  // eslint-disable-next-line func-names
  Drupal.simpleSitemapViewsUi.Arguments.prototype.uncheck = function (index) {
    this.$checkboxes.slice(index).prop('checked', false);
  };
})(jQuery, Drupal, once);
