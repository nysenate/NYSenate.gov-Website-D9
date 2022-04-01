/**
 * @file
 * Transforms links into checkboxes.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsCheckboxWidget = {
    attach: function (context) {
      Drupal.facets.makeCheckboxes(context);
    }
  };

  /**
   * Turns all facet links into checkboxes.
   */
  Drupal.facets.makeCheckboxes = function (context) {
    // Find all checkbox facet links and give them a checkbox.
    var $checkboxWidgets = $('.js-facets-checkbox-links', context)
      .once('facets-checkbox-transform');

    if ($checkboxWidgets.length > 0) {
      $checkboxWidgets.each(function (index, widget) {
        var $widget = $(widget);
        var $widgetLinks = $widget.find('.facet-item > a');

        // Add correct CSS selector for the widget. The Facets JS API will
        // register handlers on that element.
        $widget.addClass('js-facets-widget');

        // Transform links to checkboxes.
        $widgetLinks.each(Drupal.facets.makeCheckbox);

        // We have to trigger attaching of behaviours, so that Facets JS API can
        // register handlers on checkbox widgets.
        Drupal.attachBehaviors(this.parentNode, Drupal.settings);
      });

    }

    // Set indeterminate value on parents having an active trail.
    $('.facet-item--expanded.facet-item--active-trail > input').prop('indeterminate', true);
  };

  /**
   * Replace a link with a checked checkbox.
   */
  Drupal.facets.makeCheckbox = function () {
    var $link = $(this);
    var active = $link.hasClass('is-active');
    var description = $link.html();
    var href = $link.attr('href');
    var id = $link.data('drupal-facet-item-id');

    var checkbox = $('<input type="checkbox" class="facets-checkbox">')
      .attr('id', id)
      .data($link.data())
      .data('facetsredir', href);
    var label = $('<label for="' + id + '">' + description + '</label>');

    checkbox.on('change.facets', function (e) {
      e.preventDefault();

      var $widget = $(this).closest('.js-facets-widget');

      Drupal.facets.disableFacet($widget);
      $widget.trigger('facets_filter', [ href ]);
    });

    if (active) {
      checkbox.attr('checked', true);
      label.find('.js-facet-deactivate').remove();
    }

    $link.before(checkbox).before(label).hide();

  };

  /**
   * Disable all facet checkboxes in the facet and apply a 'disabled' class.
   *
   * @param {object} $facet
   *   jQuery object of the facet.
   */
  Drupal.facets.disableFacet = function ($facet) {
    $facet.addClass('facets-disabled');
    $('input.facets-checkbox', $facet).click(Drupal.facets.preventDefault);
    $('input.facets-checkbox', $facet).attr('disabled', true);
  };

  /**
   * Event listener for easy prevention of event propagation.
   *
   * @param {object} e
   *   Event.
   */
  Drupal.facets.preventDefault = function (e) {
    e.preventDefault();
  };

})(jQuery, Drupal);
