/**
 * @file
 * Facets views Link widgets handling.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Handle link widgets.
   */
  Drupal.behaviors.facetsLinkWidget = {
    attach: function (context) {
      var $linkFacets = $('.js-facets-links', context)
        .once('js-facets-link-on-click');

      // We are using list wrapper element for Facet JS API.
      if ($linkFacets.length > 0) {
        $linkFacets
          .each(function (index, widget) {
            var $widget = $(widget);
            var $widgetLinks = $widget.find('.facet-item > a');

            // Click on link will call Facets JS API on widget element.
            var clickHandler = function (e) {
              e.preventDefault();

              $widget.trigger('facets_filter', [$(this).attr('href')]);
            };

            // Add correct CSS selector for the widget. The Facets JS API will
            // register handlers on that element.
            $widget.addClass('js-facets-widget');

            // Add handler for clicks on widget links.
            $widgetLinks.on('click', clickHandler);

            // We have to trigger attaching of behaviours, so that Facets JS API can
            // register handlers on link widgets.
            Drupal.attachBehaviors(this.parentNode, Drupal.settings);
          });
      }
    }
  };

})(jQuery, Drupal);
