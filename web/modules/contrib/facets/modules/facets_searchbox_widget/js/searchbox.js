/**
 * @file
 * Provides the searchbox functionality.
 */

(function ($) {

  'use strict';

  Drupal.facets = Drupal.facets || {};

  Drupal.behaviors.facets_searchbox = {
    attach: function (context, settings) {

      const $facetsWidgetSearchbox = $('.facets-widget-searchbox', context);

      $facetsWidgetSearchbox.on("keyup", function () {
        let $facetsWidgetSearchboxNoResult = $('.facets-widget-searchbox-no-result', context);
        let $targetList = $(this).next('.facets-widget-searchbox-list', context);
        let targetListId = $targetList.attr('data-drupal-facet-id');
        let $facetsSoftLimitLink = $targetList.next('.facets-soft-limit-link', context);
        let filter = $facetsWidgetSearchbox.val().toUpperCase();
        let displayCount = 0;
        let display = getDisplayBehavior.call(this);

        $("[data-drupal-facet-id='" + targetListId + "'] li").each(function () {
          if (filter !== '') {
            search.call(this, filter, display, $targetList);
          } else {
            displayCount = resetSearch.call(this, $facetsSoftLimitLink, display, displayCount);
          }
        });

        handleNoResults(targetListId, $facetsWidgetSearchboxNoResult);
      });

      function search(filter, display, $targetList) {
        let value = $(this).find('.facet-item__value').html();

        if (value.toUpperCase().indexOf(filter) === 0) {
          if (!$(this).hasClass('hide-if-no-result')) {
            $(this).css('display', display);
          }
          $targetList.next('.facets-soft-limit-link', context).css('display', 'inline');
        } else {
          if (!$(this).hasClass('facet-item--expanded')) {
            $(this).css('display', 'none');
          } else {
            $(this).addClass('hide-if-no-result');
          }

          $targetList.next('.facets-soft-limit-link', context).css('display', 'none');
        }
      }

      function resetSearch($facetsSoftLimitLink, display, displayCount) {
        if ($facetsSoftLimitLink.length === 0 || $facetsSoftLimitLink.hasClass('open')) {
          if (!$(this).hasClass('hide-if-no-result')) {
            $(this).css('display', display);
          }
        } else {
          if (displayCount >= 5) {
            if (!$(this).hasClass('facet-item--expanded')) {
              $(this).css('display', 'none');
            } else {
              $(this).addClass('hide-if-no-result');
            }
          } else {
            if (!$(this).hasClass('hide-if-no-result')) {
              $(this).css('display', display);
            }
            displayCount += 1;
          }
        }
        $facetsSoftLimitLink.css('display', 'inline');

        return displayCount;
      }

      function getDisplayBehavior() {
        switch ($(this).attr('data-type')) {
          case 'checkbox':
            return 'flex';

          case 'links':
            return 'inline';
        }
      }

      function handleNoResults(targetListId, $facetsWidgetSearchboxNoResult) {
        if ($("[data-drupal-facet-id='" + targetListId + "'] li:visible:not(.hide-if-no-result)").length === 0) {
          $facetsWidgetSearchboxNoResult.removeClass('hide');
          $('.hide-if-no-result').addClass('hide');
        } else {
          $facetsWidgetSearchboxNoResult.addClass('hide');
          $('.hide-if-no-result').removeClass('hide');
        }
      }

    }
  };

})(jQuery);
