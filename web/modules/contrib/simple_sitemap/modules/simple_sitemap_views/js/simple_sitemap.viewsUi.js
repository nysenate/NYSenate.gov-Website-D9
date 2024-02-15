/**
 * @file
 * Views UI helpers for Simple XML Sitemap display extender.
 */

(function ($, Drupal, once) {
  Drupal.simpleSitemapViewsUi = {};

  Drupal.behaviors.simpleSitemapViewsUiArguments = {
    attach: function attach() {
      let $arguments = $(once('simple-sitemap-views-ui-arguments', '.indexed-arguments'));

      if ($arguments.length) {
        $arguments.each(function () {
          let $checkboxes = $(this).find('input[type="checkbox"]');

          if ($checkboxes.length) {
            new Drupal.simpleSitemapViewsUi.Arguments($checkboxes);
          }
        });
      }
    }
  };

  Drupal.simpleSitemapViewsUi.Arguments = function ($checkboxes) {
    this.$checkboxes = $checkboxes;
    this.$checkboxes.on('change', $.proxy(this, 'changeHandler'));
  };

  Drupal.simpleSitemapViewsUi.Arguments.prototype.changeHandler = function (e) {
    let $checkbox = $(e.target), index = this.$checkboxes.index($checkbox);
    $checkbox.prop('checked') ? this.check(index) : this.uncheck(index);
  };

  Drupal.simpleSitemapViewsUi.Arguments.prototype.check = function (index) {
    this.$checkboxes.slice(0, index).prop('checked', true);
  };

  Drupal.simpleSitemapViewsUi.Arguments.prototype.uncheck = function (index) {
    this.$checkboxes.slice(index).prop('checked', false);
  };

})(jQuery, Drupal, once);
